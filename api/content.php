<?php
/**
 * api/content.php
 * GET  ?type=quiz|knowledge|research|myths  → returns content array
 * POST { type, items[], admin_token }        → overwrites content array
 *
 * SECURITY HARDENED: Password hashing, CORS whitelist, rate limiting
 */

require_once __DIR__ . '/utils.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$VALID_TYPES = ['quiz_questions', 'knowledge_articles', 'research_papers', 'myth_busters', 'peer_recognitions', 'photo_wall', 'faq_items', 'social_settings'];

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
        'photo_wall'         => 'gallery',
        'faq_items'          => 'faq',
        'social_settings'    => 'settings'
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
    if ($items === null) {
        respond(['success' => false, 'data' => null, 'error' => 'items is required'], 400);
    }

    $dataToWrite = is_array($items) ? $items : [$items];
    if (writeContentToDB($type, $type === 'social_settings' ? $items : $dataToWrite)) {
        respond(['success' => true, 'data' => ['type' => $type, 'count' => is_array($items) ? count($items) : 1], 'error' => null]);
    } else {
        respond(['success' => false, 'data' => null, 'error' => 'Failed to write content. Check permissions.'], 500);
    }
}

// Method not allowed
respond(['success' => false, 'data' => null, 'error' => 'Method not allowed'], 405);
?>
