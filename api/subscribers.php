<?php
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$pdo = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAdmin();
    $stmt = $pdo->query("SELECT id, email, name, source, subscribed_at FROM subscribers ORDER BY subscribed_at DESC");
    respond(['success' => true, 'data' => $stmt->fetchAll(), 'error' => null]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? 'subscribe';

    if ($action === 'delete') {
        requireAdmin();
        $id = (int)($input['id'] ?? 0);
        if (!$id) respond(['success' => false, 'data' => null, 'error' => 'Invalid ID'], 400);
        $pdo->prepare("DELETE FROM subscribers WHERE id = ?")->execute([$id]);
        respond(['success' => true, 'data' => null, 'error' => null]);
    }

    $email = trim($input['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(['success' => false, 'data' => null, 'error' => 'Valid email required'], 400);
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!checkRateLimit($ip, 3, 3600, 'subscribe')) {
        respond(['success' => false, 'data' => null, 'error' => 'Too many attempts. Please try again later.'], 429);
    }

    $name   = substr(trim($input['name']   ?? ''), 0, 100);
    $source = substr(trim($input['source'] ?? 'homepage'), 0, 50);

    try {
        $pdo->prepare("INSERT INTO subscribers (email, name, source) VALUES (?, ?, ?)")
            ->execute([$email, $name, $source]);
        respond(['success' => true, 'data' => ['message' => 'Subscribed!'], 'error' => null]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            respond(['success' => true, 'data' => ['message' => 'Already subscribed!'], 'error' => null]);
        }
        error_log('Subscriber insert: ' . $e->getMessage());
        respond(['success' => false, 'data' => null, 'error' => 'Could not subscribe. Please try again.'], 500);
    }
}

respond(['success' => false, 'data' => null, 'error' => 'Method not allowed'], 405);
