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

// ── READ ────────────────────────────────────────────────────────────────────
function readContent(): array {
    if (!file_exists(CONTENT_FILE)) {
        return ['quiz_questions' => [], 'knowledge_articles' => [],
                'research_papers' => [], 'myth_busters' => []];
    }
    return json_decode(file_get_contents(CONTENT_FILE), true) ?? [];
}

// ── WRITE ───────────────────────────────────────────────────────────────────
function writeContent(array $data): bool {
    $dir = dirname(CONTENT_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return file_put_contents(CONTENT_FILE, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

// ── AUTH ────────────────────────────────────────────────────────────────────
function isAuthorized(): bool {
    // Default password
    $savedPass = 'apollo2024';
    
    // Try to get password from settings.json if it exists
    if (file_exists(SETTINGS_FILE)) {
        $st = json_decode(file_get_contents(SETTINGS_FILE), true);
        if (isset($st['admin_pass'])) $savedPass = $st['admin_pass'];
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    // Accept 'admin_pass' (new) or 'admin_token' (old) for compatibility
    $provided = $input['admin_pass'] ?? $input['admin_token'] ?? $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
    
    return $provided === $savedPass;
}

// ── HANDLE: GET ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    global $VALID_TYPES;
    $type = $_GET['type'] ?? '';
    $content = readContent();

    if ($type && in_array($type, $VALID_TYPES, true)) {
        echo json_encode($content[$type] ?? []);
    } else {
        // Return all content types
        echo json_encode($content);
    }
    exit;
}

// ── HANDLE: POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $VALID_TYPES;
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if (!isAuthorized($input)) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $type  = $input['type']  ?? '';
    $items = $input['items'] ?? null;

    if (!in_array($type, $VALID_TYPES, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid type. Use: ' . implode(', ', $VALID_TYPES)]);
        exit;
    }

    if (!is_array($items)) {
        http_response_code(400);
        echo json_encode(['error' => 'items must be an array']);
        exit;
    }

    $content = readContent();
    $content[$type] = $items;

    if (writeContent($content)) {
        echo json_encode(['success' => true, 'type' => $type, 'count' => count($items)]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to write content. Check data/ folder permissions.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
