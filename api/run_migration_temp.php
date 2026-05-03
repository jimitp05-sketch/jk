<?php
/**
 * TEMPORARY MIGRATION RUNNER
 * ⚠️  DELETE THIS FILE FROM SERVER IMMEDIATELY AFTER USE ⚠️
 *
 * Visit: https://foxwisdom.com/api/run_migration_temp.php?secret=RESET2024
 */

if (($_GET['secret'] ?? '') !== 'RESET2024') {
    http_response_code(403);
    die('Forbidden');
}

require_once __DIR__ . '/db.php';

$statements = [
    "CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(200) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        booking_date DATE NOT NULL,
        booking_time VARCHAR(20) NOT NULL,
        reason TEXT,
        status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS email_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        to_email VARCHAR(200) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        headers TEXT DEFAULT NULL,
        status ENUM('pending','sent','failed') DEFAULT 'pending',
        attempts INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at TIMESTAMP NULL,
        INDEX idx_status_created (status, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS auth_sessions (
        token CHAR(64) NOT NULL PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS login_attempts (
        ip VARCHAR(45) NOT NULL PRIMARY KEY,
        count INT NOT NULL DEFAULT 0,
        last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS csrf_tokens (
        token CHAR(64) NOT NULL PRIMARY KEY,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS rate_limits (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        prefix_ip VARCHAR(200) NOT NULL,
        hit_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_prefix_ip_hit (prefix_ip, hit_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token_hash CHAR(64) NOT NULL,
        admin_email VARCHAR(200) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        used_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_token_hash (token_hash),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content_type VARCHAR(50) NOT NULL,
        content_key VARCHAR(100) NOT NULL,
        data JSON NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_type_key (content_type, content_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

$results = [];

try {
    $pdo = get_db_connection();

    foreach ($statements as $sql) {
        // Extract table name for reporting
        preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/i', $sql, $m);
        $table = $m[1] ?? 'unknown';
        try {
            $pdo->exec($sql);
            $results[] = ['table' => $table, 'status' => 'OK ✅'];
        } catch (Exception $e) {
            $results[] = ['table' => $table, 'status' => 'ERROR ❌: ' . $e->getMessage()];
        }
    }

} catch (Exception $e) {
    echo "<p style='color:red'>DB Connection Failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

echo "<h2 style='font-family:monospace'>Migration Results</h2>";
echo "<table border='1' cellpadding='6' style='font-family:monospace;border-collapse:collapse'>";
echo "<tr><th>Table</th><th>Status</th></tr>";
foreach ($results as $r) {
    $color = str_contains($r['status'], 'OK') ? 'green' : 'red';
    echo "<tr><td>{$r['table']}</td><td style='color:{$color}'>{$r['status']}</td></tr>";
}
echo "</table>";
echo "<p style='color:red'><strong>⚠️ DELETE this file from the server now!</strong></p>";
echo "<p><a href='/api/reset_pass_temp.php?secret=RESET2024'>→ Now run the password reset</a></p>";
