<?php
/**
 * api/diya.php
 *
 * PUBLIC  GET              → returns approved diyas (with prayer messages)
 * PUBLIC  POST action=light → light a new diya (goes to pending/auto-approved based on settings)
 * ADMIN   POST action=approve/reject/delete → manage diyas
 * PUBLIC  GET  ?action=count → returns total count only
 * PUBLIC  GET  ?action=recent → returns last 10 diyas
 *
 * Database: Uses 'content' table with content_key = 'diyas'
 * Each diya: { id, name, prayer, lit_by, lit_at, status: approved|pending|rejected, ip_hash }
 */

header('Content-Type: application/json');

// ── CORS ─────────────────────────────────────────────────────────────────
$allowed_origins = [
    'https://foxwisdom.com', 'https://www.foxwisdom.com',
    'https://drjaykothari.in', 'https://www.drjaykothari.in',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} elseif (php_sapi_name() === 'cli' || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');
header('Access-Control-Allow-Credentials: true');

// ── SECURITY HEADERS ─────────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/db.php';

function respond(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getPDO() { return get_db_connection(); }

// ── READ / WRITE DIYAS ──────────────────────────────────────────────────
function readDiyas(): array {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'diyas' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ? (json_decode($row['data'], true) ?: []) : [];
}

function writeDiyas(array $diyas): bool {
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        INSERT INTO content (content_type, content_key, data)
        VALUES ('community', 'diyas', ?)
        ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
    ");
    return $stmt->execute([json_encode($diyas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
}

// ── AUTH CHECK ───────────────────────────────────────────────────────────
function isAdmin(array $input = []): bool {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'site_settings' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    if (!$row) return false;
    $st = json_decode($row['data'], true);
    $savedPass = $st['admin_pass'] ?? '';
    $provided = $input['admin_pass'] ?? $input['admin_token'] ?? $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
    if (empty($provided)) return false;
    if (str_starts_with($savedPass, '$2y$') || str_starts_with($savedPass, '$argon2id$')) {
        return password_verify($provided, $savedPass);
    }
    return $provided === $savedPass;
}

// ── RATE LIMITING (for public diya lighting) ─────────────────────────────
function checkDiyaRateLimit(string $ip, int $max = 5, int $window = 300): bool {
    $file = __DIR__ . '/../data/diya_rate_limits.json';
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

// ── INPUT SANITIZER ──────────────────────────────────────────────────────
function clean(string $val, int $maxLen = 500): string {
    $val = trim($val);
    $val = strip_tags($val);
    $val = htmlspecialchars($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (mb_strlen($val) > $maxLen) $val = mb_substr($val, 0, $maxLen);
    return $val;
}

// ══════════════════════════════════════════════════════════════════════════
// GET
// ══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $diyas = readDiyas();

    // Count only
    if ($action === 'count') {
        $approved = array_filter($diyas, fn($d) => ($d['status'] ?? '') === 'approved');
        respond(['success' => true, 'count' => count($approved)]);
    }

    // Recent 10
    if ($action === 'recent') {
        $approved = array_values(array_filter($diyas, fn($d) => ($d['status'] ?? '') === 'approved'));
        usort($approved, fn($a, $b) => strtotime($b['lit_at'] ?? '0') - strtotime($a['lit_at'] ?? '0'));
        $recent = array_slice($approved, 0, 10);
        // Strip ip_hash for public
        $recent = array_map(fn($d) => array_diff_key($d, ['ip_hash' => 1]), $recent);
        respond(['success' => true, 'data' => $recent]);
    }

    // Admin: all diyas (including pending)
    if ($action === 'admin') {
        if (!isAdmin($_GET)) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        respond(['success' => true, 'data' => $diyas]);
    }

    // Default: all approved diyas (public)
    $approved = array_values(array_filter($diyas, fn($d) => ($d['status'] ?? '') === 'approved'));
    // Strip ip_hash
    $approved = array_map(fn($d) => array_diff_key($d, ['ip_hash' => 1]), $approved);
    respond(['success' => true, 'data' => $approved, 'count' => count($approved)]);
}

// ══════════════════════════════════════════════════════════════════════════
// POST
// ══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // ── PUBLIC: Light a Diya ──────────────────────────────────────────────
    if ($action === 'light') {
        // Rate limit: 5 diyas per 5 minutes per IP
        if (!checkDiyaRateLimit($clientIP)) {
            respond(['success' => false, 'error' => 'You can light up to 5 diyas every 5 minutes. Please wait.'], 429);
        }

        $name = clean($input['name'] ?? '', 100);
        $prayer = clean($input['prayer'] ?? '', 500);
        $litBy = clean($input['lit_by'] ?? '', 100);

        if (empty($name)) {
            respond(['success' => false, 'error' => 'Please tell us who this diya is for.'], 400);
        }

        $diyas = readDiyas();
        $newDiya = [
            'id' => 'diya_' . bin2hex(random_bytes(8)),
            'name' => $name,
            'prayer' => $prayer,
            'lit_by' => $litBy,
            'lit_at' => date('Y-m-d H:i:s'),
            'status' => 'approved', // Auto-approve diyas (they're prayers, not reviews)
            'ip_hash' => hash('sha256', $clientIP . date('Y-m'))
        ];

        array_unshift($diyas, $newDiya); // newest first
        writeDiyas($diyas);

        // Return the diya (without ip_hash)
        $publicDiya = array_diff_key($newDiya, ['ip_hash' => 1]);
        respond([
            'success' => true,
            'data' => $publicDiya,
            'count' => count(array_filter($diyas, fn($d) => ($d['status'] ?? '') === 'approved'))
        ]);
    }

    // ── ADMIN: Approve / Reject / Delete ──────────────────────────────────
    if (in_array($action, ['approve', 'reject', 'delete'])) {
        if (!isAdmin($input)) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $targetId = $input['id'] ?? '';
        if (empty($targetId)) {
            respond(['success' => false, 'error' => 'Missing diya ID'], 400);
        }

        $diyas = readDiyas();
        $found = false;

        foreach ($diyas as $i => &$diya) {
            if (($diya['id'] ?? '') === $targetId) {
                $found = true;
                if ($action === 'delete') {
                    array_splice($diyas, $i, 1);
                } else {
                    $diya['status'] = ($action === 'approve') ? 'approved' : 'rejected';
                }
                break;
            }
        }
        unset($diya);

        if (!$found) {
            respond(['success' => false, 'error' => 'Diya not found'], 404);
        }

        writeDiyas($diyas);
        respond(['success' => true, 'action' => $action, 'id' => $targetId]);
    }

    // ── ADMIN: Bulk update (for import) ───────────────────────────────────
    if ($action === 'bulk_update') {
        if (!isAdmin($input)) {
            respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        $items = $input['items'] ?? [];
        if (!is_array($items)) {
            respond(['success' => false, 'error' => 'items must be an array'], 400);
        }
        writeDiyas($items);
        respond(['success' => true, 'count' => count($items)]);
    }

    respond(['success' => false, 'error' => 'Invalid action'], 400);
}

respond(['success' => false, 'error' => 'Method not allowed'], 405);
?>
