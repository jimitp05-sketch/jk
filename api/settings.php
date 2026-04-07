<?php
/**
 * api/settings.php
 * GET  → returns public site settings (WA number, ICU phone, site name)
 * POST → updates settings (requires X-Admin-Token header or token in body)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

error_reporting(0); // Suppress warnings to prevent breaking JSON output
ini_set('display_errors', 0);

define('DATA_FILE', __DIR__ . '/../data/settings.json');
define('ADMIN_TOKEN', getenv('APOLLO_ADMIN_TOKEN') ?: 'apollo_admin_2026');

// ── READ ────────────────────────────────────────────────────────────────────
function readSettings(): array {
    $defaults = [
        'wa_number'   => '919999999999',
        'wa_message'  => 'Hello, I would like to consult Dr. Jay Kothari',
        'icu_phone'   => '18605001066',
        'site_name'   => 'Dr. Jay Kothari',
        'hero_title'  => 'When Seconds Define Survival.',
        'hero_tagline' => "Gujarat's frontline of critical care — 30 years, 10,000 lives. Dr. Jay Kothari leads the most complex cases at Apollo Hospitals, Ahmedabad.",
        'hero_empathy' => "We know you're scared. You're in the right place.",
        'admin_user'  => 'admin',
        'admin_pass'  => 'apollo2024'
    ];
    if (!file_exists(DATA_FILE)) return $defaults;
    $content = @file_get_contents(DATA_FILE);
    if (!$content) return $defaults;
    $saved = json_decode($content, true) ?: [];
    return array_merge($defaults, $saved);
}

function writeSettings(array $data): bool {
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) return false;
    }
    if (!is_writable($dir)) return false;
    return @file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

// ── AUTH ────────────────────────────────────────────────────────────────────
function isAuthorized(): bool {
    $settings = readSettings();
    $savedPass = $settings['admin_pass'] ?? 'apollo2024';
    
    // Check for password in header or body
    $tokenHeader = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $passInBody = $input['admin_pass'] ?? $input['admin_token'] ?? '';
    
    return ($tokenHeader === $savedPass || $passInBody === $savedPass);
}

// ── HANDLE ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $settings = readSettings();
    
    // Check if this is a login check (requires auth)
    if (isset($_GET['action']) && $_GET['action'] === 'check_auth') {
        if (!isAuthorized()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        echo json_encode(['success' => true, 'user' => $settings['admin_user']]);
        exit;
    }

    // Public settings (NEVER return admin_pass)
    echo json_encode([
        'wa_number'  => $settings['wa_number'],
        'wa_message' => $settings['wa_message'],
        'icu_phone'  => $settings['icu_phone'],
        'site_name'  => $settings['site_name'],
        'hero_title' => $settings['hero_title'],
        'hero_tagline' => $settings['hero_tagline'],
        'hero_empathy' => $settings['hero_empathy'],
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isAuthorized()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $current = readSettings();

    // Allow updating all keys including admin credentials
    $allowed = ['wa_number', 'wa_message', 'icu_phone', 'site_name', 'hero_title', 'hero_tagline', 'hero_empathy', 'admin_user', 'admin_pass'];
    foreach ($allowed as $key) {
        if (isset($input[$key])) {
            $current[$key] = trim((string)$input[$key]);
        }
    }
    // Handle the explicit new_admin_pass field from the frontend
    if (isset($input['new_admin_pass']) && !empty(trim($input['new_admin_pass']))) {
        $current['admin_pass'] = trim((string)$input['new_admin_pass']);
    }

    if (writeSettings($current)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to write settings.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
