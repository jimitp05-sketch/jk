<?php
/**
 * TEMPORARY PASSWORD RESET SCRIPT
 * ⚠️  DELETE THIS FILE FROM HOSTINGER IMMEDIATELY AFTER USE ⚠️
 * 
 * Visit: https://drjaykothari.in/api/reset_pass_temp.php?secret=RESET2024
 */

// Simple secret to prevent unauthorized access
if (($_GET['secret'] ?? '') !== 'RESET2024') {
    http_response_code(403);
    die('Forbidden');
}

require_once __DIR__ . '/db.php';

$newPassword = 'apollo2024';
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    $pdo = get_db_connection();

    // 1. Clear login lockouts & rate limits
    $pdo->exec("DELETE FROM login_attempts");
    $pdo->exec("DELETE FROM rate_limits");

    // 2. Read existing settings
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'site_settings' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    $settings = $row ? (json_decode($row['data'], true) ?: []) : [];

    // 3. Reset password
    $settings['admin_user'] = 'admin';
    $settings['admin_pass'] = $newHash;

    // 4. Save back
    $pdo->prepare("
        INSERT INTO content (content_type, content_key, data)
        VALUES ('settings', 'site_settings', ?)
        ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
    ")->execute([json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

    echo "<h2 style='font-family:monospace;color:green'>✅ Done!</h2>";
    echo "<p>Username: <strong>admin</strong></p>";
    echo "<p>Password: <strong>apollo2024</strong></p>";
    echo "<p style='color:red'><strong>⚠️ DELETE this file from Hostinger now!</strong></p>";

} catch (Exception $e) {
    http_response_code(500);
    echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
