<?php
/**
 * api/content.php
 * GET  ?type=quiz|knowledge|research|myths  → returns content array
 * POST { type, items[], admin_token }        → overwrites content array
 *
 * SECURITY HARDENED: Password hashing, CORS whitelist, rate limiting
 */

header('Content-Type: application/json');

// ── CORS WHITELIST ───────────────────────────────────────────────────────
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
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');

// ── SECURITY HEADERS ─────────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$VALID_TYPES = ['quiz_questions', 'knowledge_articles', 'research_papers', 'myth_busters', 'peer_recognitions', 'photo_wall'];

// ── RESPOND HELPER ───────────────────────────────────────────────────────────
function respond(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── DATABASE HELPERS ─────────────────────────────────────────────────────────

function readContentFromDB(string $type = ''): array {
    $pdo = get_db_connection();
    if ($type) {
        $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = ? LIMIT 1");
        $stmt->execute([$type]);
        $row = $stmt->fetch();
        return $row ? json_decode($row['data'], true) : [];
    } else {
        $stmt = $pdo->query("SELECT content_key, data FROM content");
        $all = [];
        while ($row = $stmt->fetch()) {
            $all[$row['content_key']] = json_decode($row['data'], true);
        }
        return $all;
    }
}

function writeContentToDB(string $type, array $data): bool {
    $pdo = get_db_connection();
    // Map section type to content_type column (optional categorization)
    $typeMap = [
        'quiz_questions'     => 'quiz',
        'myth_busters'       => 'quiz',
        'research_papers'    => 'research',
        'knowledge_articles' => 'knowledge',
        'peer_recognitions'  => 'reviews',
        'photo_wall'         => 'gallery'
    ];
    $contentType = $typeMap[$type] ?? 'other';
    
    $stmt = $pdo->prepare("
        INSERT INTO content (content_type, content_key, data) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
    ");
    return $stmt->execute([$contentType, $type, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
}

// ── HANDLE: GET ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? '';
    $content = readContentFromDB();
    if ($type && !in_array($type, $VALID_TYPES, true)) {
        respond(['success' => false, 'data' => null, 'error' => 'Invalid type'], 400);
    }
    $payload = $type ? ($content[$type] ?? []) : $content;

    // Deduplicate knowledge_articles by id
    if ($type === 'knowledge_articles' && is_array($payload)) {
        $seen = [];
        $deduped = [];
        foreach (array_reverse($payload) as $item) {
            $key = $item['id'] ?? null;
            if ($key && !isset($seen[$key])) {
                $seen[$key] = true;
                $deduped[] = $item;
            }
        }
        $payload = array_reverse($deduped);
    }

    respond(['success' => true, 'data' => $payload, 'error' => null]);
}

// ── HANDLE: POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Rate limit
    if (!checkRateLimit($clientIP, 10, 60, 'content')) {
        respond(['success' => false, 'data' => null, 'error' => 'Rate limit exceeded'], 429);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    if (!isAdmin()) {
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
    
    if (writeContentToDB($type, $items)) {
        respond(['success' => true, 'data' => ['type' => $type, 'count' => count($items)], 'error' => null]);
    } else {
        respond(['success' => false, 'data' => null, 'error' => 'Failed to write content. Check permissions.'], 500);
    }
}

// Method not allowed
respond(['success' => false, 'data' => null, 'error' => 'Method not allowed'], 405);
?>
