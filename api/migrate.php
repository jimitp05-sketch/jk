<?php
/**
 * One-time migration — run once via browser: /api/migrate.php?secret=apollo_migrate_2026
 * After running successfully, this file can be deleted.
 */
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
$secret = $_GET['secret'] ?? '';
if (!hash_equals('apollo_migrate_2026', $secret)) {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}
$pdo = get_db_connection();
$results = [];

$migrations = [
    'subscribers' => "
        CREATE TABLE IF NOT EXISTS subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(100) DEFAULT '',
            source VARCHAR(50) DEFAULT 'homepage',
            subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'wiki_entries' => "
        CREATE TABLE IF NOT EXISTS wiki_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            term VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL UNIQUE,
            category ENUM('procedure','equipment','condition','medication','acronym') NOT NULL,
            definition_plain TEXT NOT NULL,
            definition_clinical TEXT,
            related_pillar VARCHAR(100) DEFAULT '',
            meta_title VARCHAR(60) DEFAULT '',
            meta_description VARCHAR(160) DEFAULT '',
            status ENUM('published','draft') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'wiki_related_terms' => "
        CREATE TABLE IF NOT EXISTS wiki_related_terms (
            wiki_id INT NOT NULL,
            related_wiki_id INT NOT NULL,
            PRIMARY KEY (wiki_id, related_wiki_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'courses' => "
        CREATE TABLE IF NOT EXISTS courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            audience ENUM('families','clinicians','survivors','all') DEFAULT 'all',
            article_ids TEXT DEFAULT '',
            status ENUM('published','draft') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'course_completions' => "
        CREATE TABLE IF NOT EXISTS course_completions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

foreach ($migrations as $name => $sql) {
    try {
        $pdo->exec(trim($sql));
        $results[$name] = 'OK';
    } catch (PDOException $e) {
        $results[$name] = 'ERROR: ' . $e->getMessage();
    }
}

$anyError = count(array_filter($results, fn($v) => str_starts_with($v, 'ERROR:'))) > 0;
echo json_encode(['success' => !$anyError, 'results' => $results], JSON_PRETTY_PRINT);
