<?php
/**
 * AI Apollo — Get Bookings API
 * Returns all bookings from MySQL for admin panel
 * Place in: public_html/api/get_bookings.php
 * 
 * SECURITY: This endpoint is for admin panel only.
 * Add IP restriction or session check before deploying live.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ── CONFIGURATION ─────────────────────────────────────────
$DB_HOST = 'localhost';
$DB_USER = 'your_db_user';
$DB_PASS = 'your_db_password';
$DB_NAME = 'your_db_name';

// Simple token check (set this in admin.html JS too)
$token = $_GET['token'] ?? '';
$ADMIN_TOKEN = 'apollo_admin_2026'; // Change to something secure

if ($token !== $ADMIN_TOKEN) {
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
        $stmt = $pdo->query("SELECT * FROM bookings ORDER BY created_at DESC");
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'bookings' => $bookings]);

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
