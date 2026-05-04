<?php
/**
 * AI Apollo — Database Connection Utility
 * 
 * Centralizes PDO initialization with production-grade settings:
 * - Persistent connections (optional)
 * - Exception error mode
 * - Native prepared statement emulation (False, for security)
 * - Utf8mb4 charset reinforcement
 */

function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $config = require __DIR__ . '/config.php';

    try {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            // Force utf8mb4 at the connection level — handles Hindi, Gujarati, emoji and all Unicode
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
        // Extra safety: explicitly set charset after connection
        $pdo->exec("SET CHARACTER SET utf8mb4");
        $pdo->exec("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");
        return $pdo;
    } catch (PDOException $e) {
        // Log error and return null or throw depending on context
        // In API context, we'll throw to be caught by the handler
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed. Please try again later.");
    }
}
