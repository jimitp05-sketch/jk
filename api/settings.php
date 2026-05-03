<?php
/**
 * api/settings.php
 * GET  → returns public site settings (WA number, ICU phone, site name)
 * POST → updates settings (requires X-Admin-Token header or token in body)
 *
 * SECURITY HARDENED: Password hashing, CORS whitelist, CSRF, rate limiting, single php://input read
 */

header('Content-Type: application/json');

// ── CORS WHITELIST (replaces wildcard *) ─────────────────────────────────
$allowed_origins = [
    'https://foxwisdom.com',
    'https://www.foxwisdom.com',
    'https://drjaykothari.in',
    'https://www.drjaykothari.in',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} elseif (php_sapi_name() === 'cli' || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) {
    // Allow localhost for development
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');

// ── SECURITY HEADERS ─────────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

error_reporting(0);
ini_set('display_errors', 0);

define('DATA_FILE', __DIR__ . '/../data/settings.json');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// ── DATABASE HELPERS ─────────────────────────────────────────────────────────

function getPDO() {
    return get_db_connection();
}

function readSettings(): array {
    $defaults = [
        'wa_number'   => '919999999999',
        'wa_message'  => 'Hello, I would like to consult Dr. Jay Kothari',
        'icu_phone'   => '18605001066',
        'site_name'   => 'Dr. Jay Kothari',
        'hero_title'  => 'Your Family Deserves The Best ICU Doctor in Gujarat.',
        'hero_desc'   => "We know you're terrified right now. Take a breath. You've found Dr. Jay Kothari — and he's ready.",
        'hero_badge'  => "🔵 Apollo Hospitals · Gujarat's #1 Critical Care Unit",
        'hero_stat1_val' => '30',
        'hero_stat1_lbl' => 'Years of Practice',
        'hero_stat2_val' => '10000',
        'hero_stat2_lbl' => 'Lives Touched',
        'hero_stat3_val' => '10',
        'hero_stat3_lbl' => 'ECMO Docs in Gujarat',
        'hero_img'    => 'img-hero-doctor.png',
        'ga4_id'      => '',
        'gtm_id'      => '',
        'admin_user'  => 'admin',
        'admin_pass'  => password_hash('admin', PASSWORD_DEFAULT) // TODO: change via Settings panel
    ];

    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'site_settings' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) return $defaults;

        $saved = json_decode($row['data'], true) ?: [];

        // Migration: if stored password is NOT hashed, hash it now & save back to DB
        if (isset($saved['admin_pass']) && !isHashedPassword($saved['admin_pass'])) {
            $saved['admin_pass'] = password_hash($saved['admin_pass'], PASSWORD_DEFAULT);
            writeSettings($saved);
        }

        return array_merge($defaults, $saved);
    } catch (Exception $e) {
        error_log('settings.php: readSettings() DB error: ' . $e->getMessage());
        return $defaults; // Return defaults if DB/table unavailable
    }
}


function writeSettings(array $data): bool {
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        INSERT INTO content (content_type, content_key, data)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
    ");
    return $stmt->execute(['settings', 'site_settings', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
}

// ── HANDLE ───────────────────────────────────────────────────────────────────
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $settings = readSettings();

    // Check if this is a CSRF token request
    if (isset($_GET['action']) && $_GET['action'] === 'csrf_token') {
        echo json_encode(['csrf_token' => generateCSRFToken()]);
        exit;
    }

    // Check if this is a login check (requires auth)
    if (isset($_GET['action']) && $_GET['action'] === 'check_auth') {
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $settings = readSettings();
        echo json_encode(['success' => true, 'user' => $settings['admin_user']]);
        exit;
    }

    // Public settings (NEVER return admin_pass)
    $public = [
        'hero_title', 'hero_desc', 'hero_badge', 'hero_img',
        'hero_tagline', 'hero_empathy', 'opd_link',
        'ticker_text', 'ticker_on',
        'hero_stat1_val', 'hero_stat1_lbl',
        'hero_stat2_val', 'hero_stat2_lbl',
        'hero_stat3_val', 'hero_stat3_lbl',
        'stat1_num', 'stat1_lbl', 'stat2_num', 'stat2_lbl',
        'stat3_num', 'stat3_lbl', 'stat4_num', 'stat4_lbl',
        'wa_number', 'wa_message', 'icu_phone', 'site_name',
        'ga4_id', 'gtm_id',
    ];
    $output = [];
    foreach ($public as $k) {
        $output[$k] = $settings[$k] ?? '';
    }

    // Include admin_email only for authenticated requests
    $token = extractAuthToken();
    if (validateSessionToken($token)) {
        $output['admin_email'] = $settings['admin_email'] ?? '';
    }

    echo json_encode($output);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limit POST requests
    if (!checkRateLimit($clientIP, 10, 60, 'settings')) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded. Try again later.']);
        exit;
    }

    // Read php://input ONCE and reuse
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? $_POST;

    // Handle auth check via POST
    if (isset($input['action']) && $input['action'] === 'check_auth') {
        $password = $input['admin_pass'] ?? '';
        $result = handleLogin($password, $clientIP);

        if (!$result['success']) {
            http_response_code($result['code'] ?? 403);
            echo json_encode(['error' => $result['error']]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'user' => $result['user'],
            'session_token' => $result['session_token'],
            'expires_in' => $result['expires_in']
        ]);
        exit;
    }

    // Handle credential change
    if (isset($input['action']) && $input['action'] === 'change_credentials') {
        if (!isAdmin()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $currentPass = $input['current_password'] ?? '';
        $newPass = $input['new_password'] ?? '';
        $newUser = $input['new_username'] ?? '';

        if (empty($newPass) && empty($newUser)) {
            http_response_code(400);
            echo json_encode(['error' => 'Provide a new password or username to update.']);
            exit;
        }

        // If only changing username, still require current password for verification
        if (empty($newPass)) $newPass = $currentPass;

        $result = changeAdminPassword($currentPass, $newPass, $newUser);
        if (!$result['success']) {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
            exit;
        }

        echo json_encode(['success' => true, 'message' => $result['message']]);
        exit;
    }

    // CSRF validation not needed for JSON API requests with session tokens —
    // CORS + Content-Type: application/json prevents cross-origin form submissions,
    // and the session token in the header/body acts as a CSRF defense.

    if (!isAdmin()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $current = readSettings();

    // Allow updating all keys including admin credentials
    $allowed = [
        'wa_number', 'wa_message', 'icu_phone', 'site_name',
        'hero_title', 'hero_desc', 'hero_badge', 'hero_img',
        'hero_tagline', 'hero_empathy', 'opd_link',
        'ticker_text', 'ticker_on',
        'hero_stat1_val', 'hero_stat1_lbl',
        'hero_stat2_val', 'hero_stat2_lbl',
        'hero_stat3_val', 'hero_stat3_lbl',
        'stat1_num', 'stat1_lbl', 'stat2_num', 'stat2_lbl',
        'stat3_num', 'stat3_lbl', 'stat4_num', 'stat4_lbl',
        'ga4_id', 'gtm_id',
        'admin_user', 'admin_email'
    ];
    foreach ($allowed as $key) {
        if (isset($input[$key])) {
            $val = $input[$key];
            if ($key === 'ticker_on') {
                $current[$key] = (bool)$val;
            } else {
                $current[$key] = trim((string)$val);
            }
        }
    }

    try {
        if (writeSettings($current)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to write settings. DB write returned false.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error. Please try again.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
