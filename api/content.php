<?php
/**
 * api/content.php
 * GET  ?type=quiz|knowledge|research|myths  → returns content array
 * POST { type, items[], admin_token }        → overwrites content array
 *
 * Empty array in content.json = website falls back to hardcoded content.
 * Non-empty array = website uses server data instead.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('CONTENT_FILE', __DIR__ . '/../data/content.json');
define('ADMIN_TOKEN',  getenv('APOLLO_ADMIN_TOKEN') ?: 'apollo_admin_2026');

$VALID_TYPES = ['quiz_questions', 'knowledge_articles', 'research_papers', 'myth_busters'];

// ── RESPONSE HELPER ─────────────────────────────────────────────────────────────
function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// ── LOGGING ───────────────────────────────────────────────────────────────────
function log_error($msg) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/api.log';
    $entry = '[' . date('c') . '] ' . $_SERVER['REMOTE_ADDR'] . ' ' . $msg . "\n";
    file_put_contents($logFile, $entry, FILE_APPEND);
}

// ── READ ────────────────────────────────────────────────────────────────────
function readContent(): array {
    if (!file_exists(CONTENT_FILE)) {
        // create empty skeleton if missing
        $empty = ['quiz_questions' => [], 'knowledge_articles' => [], 'research_papers' => [], 'myth_busters' => []];
        writeContent($empty);
        return $empty;
    }
    $json = file_get_contents(CONTENT_FILE);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        log_error('Failed to decode content.json');
        // fallback to empty structure
        $data = ['quiz_questions' => [], 'knowledge_articles' => [], 'research_papers' => [], 'myth_busters' => []];
    }
    return $data;
}

// ── WRITE ───────────────────────────────────────────────────────────────────
function writeContent(array $data): bool {
    $dir = dirname(CONTENT_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT);
    $result = file_put_contents(CONTENT_FILE, $json);
    if ($result === false) {
        log_error('Failed to write content.json');
        return false;
    }
    chmod(CONTENT_FILE, 0644);
    return true;
}

// ── AUTH ────────────────────────────────────────────────────────────────────
function isAuthorized(array $input = []): bool {
    $savedPass = 'apollo2024';
    // allow override via settings.json
    $settingsFile = __DIR__ . '/../data/settings.json';
    if (file_exists($settingsFile)) {
        $st = json_decode(file_get_contents($settingsFile), true);
        if (isset($st['admin_pass'])) $savedPass = $st['admin_pass'];
    }
    $provided = $input['admin_pass'] ?? $input['admin_token'] ?? $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
    return $provided === $savedPass;
}

// ── HANDLE: GET ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? '';
    $content = readContent();
    if ($type && !in_array($type, $VALID_TYPES, true)) {
        respond(['success' => false, 'data' => null, 'error' => 'Invalid type'], 400);
    }
    $payload = $type ? ($content[$type] ?? []) : $content;
    respond(['success' => true, 'data' => $payload, 'error' => null]);
}

// ── HANDLE: POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    if (!isAuthorized($input)) {
        respond(['success' => false, 'data' => null, 'error' => 'Unauthorized'], 401);
    }
    $type = $input['type'] ?? '';
    $items = $input['items'] ?? null;
    if (!in_array($type, $VALID_TYPES, true)) {
        respond(['success' => false, 'data' => null, 'error' => 'Invalid type'], 400);
    }
    if (!is_array($items)) {
        respond(['success' => false, 'data' => null, 'error' => 'items must be an array'], 400);
    }
    $content = readContent();
    $content[$type] = $items;
    if (writeContent($content)) {
        respond(['success' => true, 'data' => ['type' => $type, 'count' => count($items)], 'error' => null]);
    } else {
        respond(['success' => false, 'data' => null, 'error' => 'Failed to write content. Check permissions.'], 500);
    }
}

// Method not allowed
respond(['success' => false, 'data' => null, 'error' => 'Method not allowed'], 405);
?>
