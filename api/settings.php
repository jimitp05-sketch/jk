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

define('DATA_FILE', __DIR__ . '/../data/settings.json');
define('ADMIN_TOKEN', getenv('APOLLO_ADMIN_TOKEN') ?: 'apollo_admin_2026');

// ── READ ────────────────────────────────────────────────────────────────────
function readSettings(): array {
    if (!file_exists(DATA_FILE)) {
        return [
            'wa_number'   => '919999999999',
            'wa_message'  => 'Hello, I would like to consult Dr. Jay Kothari',
            'icu_phone'   => '18605001066',
            'site_name'   => 'Dr. Jay Kothari'
        ];
    }
    return json_decode(file_get_contents(DATA_FILE), true) ?? [];
}

// ── WRITE ───────────────────────────────────────────────────────────────────
function writeSettings(array $data): bool {
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

// ── AUTH ────────────────────────────────────────────────────────────────────
function isAuthorized(): bool {
    $tokenHeader = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
    $tokenBody   = $_POST['admin_token'] ?? '';
    $tokenJson   = json_decode(file_get_contents('php://input'), true)['admin_token'] ?? '';
    return in_array(ADMIN_TOKEN, [$tokenHeader, $tokenBody, $tokenJson], true);
}

// ── HANDLE ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $settings = readSettings();
    // Return only public keys (never return admin_token)
    echo json_encode([
        'wa_number'  => $settings['wa_number']  ?? '919999999999',
        'wa_message' => $settings['wa_message'] ?? 'Hello, I would like to consult Dr. Jay Kothari',
        'icu_phone'  => $settings['icu_phone']  ?? '18605001066',
        'site_name'  => $settings['site_name']  ?? 'Dr. Jay Kothari',
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

    // Only allow updating safe keys
    $allowed = ['wa_number', 'wa_message', 'icu_phone', 'site_name'];
    foreach ($allowed as $key) {
        if (isset($input[$key])) {
            $current[$key] = trim((string)$input[$key]);
        }
    }

    if (writeSettings($current)) {
        echo json_encode(['success' => true, 'settings' => $current]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to write settings. Check data/ folder permissions.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
