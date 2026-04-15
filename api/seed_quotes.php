<?php
/**
 * One-time script to seed starter quotes into the database.
 * Run once: php api/seed_quotes.php  OR visit /api/seed_quotes.php?admin_pass=YOUR_PASS
 *
 * Safe to re-run: checks if quotes already exist before seeding.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

// Auth check (same pattern as diya.php)
function checkAdmin(): bool {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'site_settings' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    if (!$row) return false;
    $st = json_decode($row['data'], true);
    $savedPass = $st['admin_pass'] ?? '';
    $provided = $_GET['admin_pass'] ?? $_POST['admin_pass'] ?? '';
    if (empty($provided)) return false;
    if (str_starts_with($savedPass, '$2y$') || str_starts_with($savedPass, '$argon2id$')) {
        return password_verify($provided, $savedPass);
    }
    return $provided === $savedPass;
}

// Allow CLI or admin auth
if (php_sapi_name() !== 'cli' && !checkAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Pass admin_pass as query parameter.']);
    exit;
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
