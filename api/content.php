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

$VALID_TYPES = ['quiz_questions', 'knowledge_articles', 'research_papers', 'myth_busters', 'peer_recognitions', 'photo_wall', 'faq_items', 'social_settings', 'expertise_items', 'institute_recognitions', 'media_mentions', 'blocked_dates'];

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

function appendContentItem(string $type, array $item): bool {
    $items = readContentFromDB($type);
    if (!is_array($items)) $items = [];
    $items[] = $item;
    return writeContentToDB($type, $items);
}

// ── HANDLE: GET ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? '';
    $adminView = isAdmin();
    $content = readContentFromDB();
    if ($type && !in_array($type, $VALID_TYPES, true)) {
        respond(['success' => false, 'data' => null, 'error' => 'Invalid type'], 400);
    }
    $payload = $type ? ($content[$type] ?? []) : $content;

    if (!$adminView) {
        $publicModeratedTypes = ['peer_recognitions', 'photo_wall'];
        if ($type && in_array($type, $publicModeratedTypes, true) && is_array($payload)) {
            $payload = array_values(array_filter($payload, fn($item) => ($item['status'] ?? 'approved') === 'approved'));
        }

        $stripPrivate = function ($item) {
            if (is_array($item)) unset($item['ip_hash']);
            return $item;
        };

        if ($type && is_array($payload)) {
            $payload = array_map($stripPrivate, $payload);
        } elseif (!$type && is_array($payload)) {
            foreach ($payload as $key => $items) {
                if (in_array($key, $publicModeratedTypes, true) && is_array($items)) {
                    $items = array_values(array_filter($items, fn($item) => ($item['status'] ?? 'approved') === 'approved'));
                }
                if (is_array($items)) $items = array_map($stripPrivate, $items);
                $payload[$key] = $items;
            }
        }
    }

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

    $input = json_decode(file_get_contents('php://input'), true) ?? [];



    if (($input['action'] ?? '') === 'submit_photo') {
        if (!checkRateLimit($clientIP, 5, 3600, 'public_photo')) {
            respond(['success' => false, 'data' => null, 'error' => 'Too many submissions. Please try again later.'], 429);
        }

        $url = trim((string)($input['url'] ?? ''));
        $parts = parse_url($url);
        $allowedHosts = ['foxwisdom.com', 'www.foxwisdom.com', 'drjaykothari.in', 'www.drjaykothari.in'];
        $path = $parts['path'] ?? '';
        if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['https', 'http'], true)
            || !in_array(strtolower($parts['host'] ?? ''), $allowedHosts, true)
            || strpos($path, '/uploads/memories/') !== 0) {
            respond(['success' => false, 'data' => null, 'error' => 'Invalid uploaded photo URL.'], 400);
        }

        $caption = clean($input['caption'] ?? '', 300);
        if ($caption === '') {
            respond(['success' => false, 'data' => null, 'error' => 'Caption is required.'], 400);
        }

        $item = [
            'id' => 'photo_' . bin2hex(random_bytes(8)),
            'url' => $url,
            'caption' => $caption,
            'label' => clean($input['label'] ?? 'General', 50) ?: 'General',
            'name' => clean($input['name'] ?? '', 100),
            'story' => clean($input['story'] ?? '', 1000),
            'date' => clean($input['date'] ?? '', 30),
            'status' => 'pending',
            'source' => 'public_submission',
            'added' => date('c'),
            'ip_hash' => hash('sha256', $clientIP . date('Y-m')),
        ];

        if (appendContentItem('photo_wall', $item)) {
            respond(['success' => true, 'data' => ['id' => $item['id'], 'status' => 'pending'], 'error' => null]);
        }
        respond(['success' => false, 'data' => null, 'error' => 'Could not save photo submission.'], 500);
    }

    if (!isAdmin()) {
        respond(['success' => false, 'data' => null, 'error' => 'Unauthorized'], 401);
    }

    if (!checkRateLimit($clientIP, 120, 60, 'content_admin')) {
        respond(['success' => false, 'data' => null, 'error' => 'Admin rate limit exceeded'], 429);
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
