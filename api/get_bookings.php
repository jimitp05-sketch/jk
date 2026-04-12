<?php
/**
 * AI Apollo — Get Bookings API
 * Returns all bookings from MySQL for admin panel
 *
 * SECURITY HARDENED: Uses shared config.php, Authorization header (not URL), CORS whitelist
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
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Token');
header('Access-Control-Allow-Credentials: true');

// ── SECURITY HEADERS ─────────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── USE SHARED CONFIG (no more hardcoded credentials) ────────────────────
$config = require __DIR__ . '/config.php';
$DB_HOST = $config['db_host'];
$DB_USER = $config['db_user'];
$DB_PASS = $config['db_pass'];
$DB_NAME = $config['db_name'];

// ── AUTH: Accept token via Authorization header OR X-Admin-Token header ──
function getAdminPassword(): string {
    $settingsFile = __DIR__ . '/../data/settings.json';
    if (file_exists($settingsFile)) {
        $st = json_decode(file_get_contents($settingsFile), true);
        if (isset($st['admin_pass'])) return $st['admin_pass'];
    }
    return password_hash('apollo2024', PASSWORD_ARGON2ID);
}

function isAuthorized(): bool {
    $savedPass = getAdminPassword();
    
    // 1. Check Authorization: Bearer <token> header
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    $provided = '';
    if (preg_match('/Bearer\s+(.+)/i', $authHeader, $m)) {
        $provided = trim($m[1]);
    }
    
    // 2. Fallback: X-Admin-Token header
    if (!$provided) {
        $provided = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
    }
    
    // 3. Legacy fallback: token in GET URL (backward compat, will be deprecated)
    if (!$provided) {
        $provided = $_GET['token'] ?? '';
    }
    
    if (!$provided) return false;
    
    // Support both hashed and legacy plaintext
    if (str_starts_with($savedPass, '$argon2id$') || str_starts_with($savedPass, '$2y$')) {
        return password_verify($provided, $savedPass);
    }
    return $provided === $savedPass;
}

// ── RATE LIMITING ────────────────────────────────────────────────────────
function checkRateLimit(string $ip): bool {
    $file = __DIR__ . '/../data/rate_limits.json';
    $limits = [];
    if (file_exists($file)) {
        $limits = json_decode(@file_get_contents($file), true) ?: [];
    }
    $now = time();
    foreach ($limits as $k => $v) {
        $limits[$k] = array_filter($v, fn($t) => ($now - $t) < 60);
        if (empty($limits[$k])) unset($limits[$k]);
    }
    if (count($limits[$ip] ?? []) >= 30) return false; // More lenient for admin reads
    $limits[$ip][] = $now;
    @file_put_contents($file, json_encode($limits), LOCK_EX);
    return true;
}

$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!checkRateLimit($clientIP)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Rate limit exceeded']);
    exit;
}

if (!isAuthorized()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        // Add pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;
        
        $stmt = $pdo->prepare("SELECT * FROM bookings ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for pagination
        $countStmt = $pdo->query("SELECT COUNT(*) FROM bookings");
        $total = (int)$countStmt->fetchColumn();
        
        echo json_encode([
            'success' => true, 
            'bookings' => $bookings,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);

    } elseif ($action === 'update_status') {
        $id     = (int)($_GET['id'] ?? 0);
        $status = $_GET['status'] ?? '';
        $allowed = ['pending','confirmed','completed','cancelled'];
        if (!$id || !in_array($status, $allowed)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE bookings SET status=? WHERE id=?");
        $stmt->execute([$status, $id]);
        echo json_encode(['success' => true]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    error_log('Get bookings error: ' . $e->getMessage());
}
?>
