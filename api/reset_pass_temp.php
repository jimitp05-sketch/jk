<?php
/**
 * TEMPORARY PASSWORD RESET SCRIPT
 * Keep only during testing. Remove before production.
 *
 * Usage:
 *   /api/reset_pass_temp.php?secret=RESET2024
 *
 * Optional:
 *   Set TEMP_RESET_SECRET in .env to override the default testing secret.
 */

require_once __DIR__ . '/config.php';

$expectedSecret = env('TEMP_RESET_SECRET', 'RESET2024');
if (!hash_equals($expectedSecret, $_GET['secret'] ?? '')) {
    http_response_code(403);
    die('Forbidden');
}

require_once __DIR__ . '/db.php';

$newPassword = env('TEMP_RESET_PASSWORD', 'admin');
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    $pdo = get_db_connection();

    $pdo->exec("DELETE FROM login_attempts");
    $pdo->exec("DELETE FROM rate_limits");
    $pdo->exec("DELETE FROM auth_sessions");

    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'site_settings' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    $settings = $row ? (json_decode($row['data'], true) ?: []) : [];

    $settings['admin_user'] = env('TEMP_RESET_USER', 'admin');
    $settings['admin_pass'] = $newHash;

    $pdo->prepare("
        INSERT INTO content (content_type, content_key, data)
        VALUES ('settings', 'site_settings', ?)
        ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
    ")->execute([json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

    echo "<h2 style='font-family:monospace;color:green'>Done</h2>";
    echo "<p>Username: <strong>" . htmlspecialchars($settings['admin_user'], ENT_QUOTES, 'UTF-8') . "</strong></p>";
    echo "<p>Password: <strong>" . htmlspecialchars($newPassword, ENT_QUOTES, 'UTF-8') . "</strong></p>";
    echo "<p style='color:red'><strong>Temporary testing reset is active. Remove this file before production.</strong></p>";
} catch (Exception $e) {
    http_response_code(500);
    echo "<h2 style='font-family:monospace;color:red'>Error</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
}
