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

$VALID_TYPES = ['quiz_questions', 'knowledge_articles', 'research_papers', 'myth_busters', 'peer_recognitions', 'photo_wall'];

// ── RESPOND HELPER ───────────────────────────────────────────────────────────
function respond(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── DATABASE HELPERS ─────────────────────────────────────────────────────────

function getPDO() {
    return get_db_connection();
}

function readContentFromDB(string $type = ''): array {
    $pdo = getPDO();
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
    $pdo = getPDO();
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

// ── AUTH (Centralized in settings.php or shared- remains mostly same but uses DB credentials) ───────────
function isAuthorized(array $input = []): bool {
    // During migration, we still read the password from settings.json or its DB equivalent
    // Ideally, this should pull from the 'settings' row in 'content' table now.
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'site_settings' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    
    $savedPass = null;
    if ($row) {
        $st = json_decode($row['data'], true);
        if (isset($st['admin_pass'])) $savedPass = $st['admin_pass'];
    }
    
    // Fallback to config or default
    if ($savedPass === null) {
        $config = require __DIR__ . '/config.php';
        $savedPass = $config['admin_token'] ?: password_hash('apollo2024', PASSWORD_ARGON2ID);
    }
    
    $provided = $input['admin_pass'] ?? $input['admin_token'] ?? $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
    if (empty($provided)) return false;
    
    if (str_starts_with($savedPass, '$argon2id$') || str_starts_with($savedPass, '$2y$')) {
        return password_verify($provided, $savedPass);
    }
    return $provided === $savedPass;
}

// ── RATE LIMITING ────────────────────────────────────────────────────────
function checkRateLimit(string $ip, int $max = 10, int $window = 60): bool {
    $file = __DIR__ . '/../data/rate_limits.json';
    $limits = [];
    if (file_exists($file)) {
        $limits = json_decode(@file_get_contents($file), true) ?: [];
    }
    $now = time();
    foreach ($limits as $k => $v) {
        $limits[$k] = array_filter($v, fn($t) => ($now - $t) < $window);
        if (empty($limits[$k])) unset($limits[$k]);
    }
    if (count($limits[$ip] ?? []) >= $max) return false;
    $limits[$ip][] = $now;
    @file_put_contents($file, json_encode($limits), LOCK_EX);
    return true;
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
    if (!checkRateLimit($clientIP)) {
        respond(['success' => false, 'data' => null, 'error' => 'Rate limit exceeded'], 429);
    }
    
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
    
    if (writeContentToDB($type, $items)) {
        respond(['success' => true, 'data' => ['type' => $type, 'count' => count($items)], 'error' => null]);
    } else {
        respond(['success' => false, 'data' => null, 'error' => 'Failed to write content. Check permissions.'], 500);
    }
}

// Method not allowed
respond(['success' => false, 'data' => null, 'error' => 'Method not allowed'], 405);
?>
