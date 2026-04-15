<?php
/**
 * One-time script to seed starter quotes into the database.
 * Run once: php api/seed_quotes.php  OR visit /api/seed_quotes.php?admin_pass=YOUR_PASS
 *
 * Safe to re-run: checks if quotes already exist before seeding.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo json_encode(['error' => 'This script can only be run from the command line.']);
    exit(1);
}

$pdo = get_db_connection();

// Check if quotes already exist
$stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'diya_quotes' LIMIT 1");
$stmt->execute();
$row = $stmt->fetch();
$existing = $row ? (json_decode($row['data'], true) ?: []) : [];

if (count($existing) > 0) {
    echo json_encode(['message' => 'Quotes already exist (' . count($existing) . ' found). Skipping seed.', 'count' => count($existing)]);
    exit;
}

// Load starter quotes
$quotesFile = __DIR__ . '/../data/starter_quotes.json';
if (!file_exists($quotesFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Starter quotes file not found at ' . $quotesFile]);
    exit;
}

$quotes = json_decode(file_get_contents($quotesFile), true);
if (!$quotes || !is_array($quotes)) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid quotes JSON']);
    exit;
}

// Insert into content table
$stmt = $pdo->prepare("
    INSERT INTO content (content_type, content_key, data)
    VALUES ('community', 'diya_quotes', ?)
    ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
");
$stmt->execute([json_encode($quotes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

echo json_encode(['success' => true, 'message' => 'Seeded ' . count($quotes) . ' starter quotes.', 'count' => count($quotes)]);
?>
