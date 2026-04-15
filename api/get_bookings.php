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

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!checkRateLimit($clientIP, 30, 60, 'bookings')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Rate limit exceeded']);
    exit;
}

if (!isAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = get_db_connection();

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
