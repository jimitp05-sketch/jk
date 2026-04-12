<?php
/**
 * AI Apollo — Email Queue Processor (Cron Job)
 * 
 * Processes pending emails from the `email_queue` table.
 * Run via cron every 1-2 minutes:
 *   * * * * * /usr/bin/php /path/to/api/process_email_queue.php >> /path/to/logs/email_queue.log 2>&1
 * 
 * On Hostinger: Set up via hPanel → Advanced → Cron Jobs
 */

$config = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log('[EmailQueue] DB connection failed: ' . $e->getMessage());
    exit(1);
}

// ── Fetch pending emails (max 10 per run to avoid timeout) ──
$stmt = $pdo->prepare("
    SELECT id, to_email, subject, body, headers, attempts 
    FROM email_queue 
    WHERE status = 'pending' AND attempts < 3 
    ORDER BY created_at ASC 
    LIMIT 10
");
$stmt->execute();
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($emails)) {
    exit(0); // Nothing to process
}

$sent = 0;
$failed = 0;

foreach ($emails as $email) {
    $id = $email['id'];
    $attempts = $email['attempts'] + 1;
    
    // Attempt to send
    $success = @mail(
        $email['to_email'],
        $email['subject'],
        $email['body'],
        $email['headers'] ?? ''
    );
    
    if ($success) {
        $update = $pdo->prepare("UPDATE email_queue SET status = 'sent', attempts = ?, sent_at = NOW() WHERE id = ?");
        $update->execute([$attempts, $id]);
        $sent++;
    } else {
        $newStatus = ($attempts >= 3) ? 'failed' : 'pending';
        $update = $pdo->prepare("UPDATE email_queue SET status = ?, attempts = ? WHERE id = ?");
        $update->execute([$newStatus, $attempts, $id]);
        $failed++;
        error_log("[EmailQueue] Failed to send email #{$id} to {$email['to_email']} (attempt {$attempts}/3)");
    }
}

echo date('Y-m-d H:i:s') . " — Processed: {$sent} sent, {$failed} failed\n";
?>
