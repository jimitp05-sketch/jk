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

    if ($action === 'send_newsletter') {
        requireAdmin();
        $subject = trim($input['subject'] ?? '');
        $body = $input['body'] ?? '';
        $preview = trim($input['preview'] ?? '');

        if (!$subject || !$body) {
            echo json_encode(['success' => false, 'error' => 'Subject and body required']);
            exit;
        }

        // Get all subscribers
        $stmt = $pdo->query("SELECT email FROM subscribers ORDER BY subscribed_at DESC");
        $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($emails)) {
            echo json_encode(['success' => true, 'sent' => 0, 'message' => 'No subscribers to send to']);
            exit;
        }

        $sent = 0;
        $from = 'noreply@foxwisdom.com';
        $fromName = 'Dr. Jay Kothari';
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            "From: {$fromName} <{$from}>",
            "Reply-To: {$from}",
            'X-Mailer: PHP/' . phpversion()
        ];
        if ($preview) {
            $headers[] = "X-Preview-Text: {$preview}";
        }
        $headerStr = implode("\r\n", $headers);

        $htmlBody = "<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;'>"
            . "<div style='border-bottom:3px solid #0ea5e9;padding-bottom:16px;margin-bottom:24px;'>"
            . "<h2 style='color:#0a1628;margin:0;'>Dr. Jay Kothari</h2>"
            . "<p style='color:#6b7280;margin:4px 0 0;font-size:0.85rem;'>Critical Care Specialist · Apollo Hospitals, Ahmedabad</p>"
            . "</div>"
            . "<div style='line-height:1.7;color:#374151;'>" . $body . "</div>"
            . "<div style='margin-top:32px;padding-top:16px;border-top:1px solid #e5e7eb;font-size:0.8rem;color:#9ca3af;'>"
            . "<p>You received this because you subscribed at foxwisdom.com. "
            . "<a href='https://foxwisdom.com/unsubscribe?email=' style='color:#6b7280;'>Unsubscribe</a></p>"
            . "</div></body></html>";

        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if (@mail($email, $subject, $htmlBody, $headerStr)) {
                    $sent++;
                }
            }
        }

        echo json_encode(['success' => true, 'sent' => $sent, 'total' => count($emails)]);
        exit;
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
