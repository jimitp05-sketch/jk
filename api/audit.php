<?php
/**
 * Audit Log API — logs and retrieves admin actions
 */
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');
$allowed_origins = ['https://foxwisdom.com','https://www.foxwisdom.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
if (!validateSessionToken($token)) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

$log_file = __DIR__ . '/../data/audit_log.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Return recent audit entries
    $limit = min((int)($_GET['limit'] ?? 100), 500);
    if (!file_exists($log_file)) {
        echo json_encode(['success'=>true,'entries'=>[]]);
        exit;
    }
    $entries = json_decode(file_get_contents($log_file), true) ?: [];
    $entries = array_slice(array_reverse($entries), 0, $limit);
    echo json_encode(['success'=>true,'entries'=>$entries]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = trim($data['action'] ?? '');
    $section = trim($data['section'] ?? '');
    $detail = trim($data['detail'] ?? '');

    if (!$action) {
        echo json_encode(['success'=>false,'error'=>'Action required']);
        exit;
    }

    $entries = [];
    if (file_exists($log_file)) {
        $entries = json_decode(file_get_contents($log_file), true) ?: [];
    }

    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action'    => $action,
        'section'   => $section,
        'detail'    => $detail,
        'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    $entries[] = $entry;

    // Keep only last 1000 entries
    if (count($entries) > 1000) {
        $entries = array_slice($entries, -1000);
    }

    $dir = dirname($log_file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($log_file, json_encode($entries, JSON_PRETTY_PRINT));

    echo json_encode(['success'=>true]);
    exit;
}
