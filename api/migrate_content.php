<?php
/**
 * AI Apollo — CMS Migration Script
 * 
 * Migrates content from data/content.json to the MySQL `content` table.
 * Run ONCE after executing db_migration.sql (which creates the `content` table).
 * 
 * Usage: php api/migrate_content.php
 * On Hostinger: Run via SSH or from browser (then delete the file)
 */

require_once __DIR__ . '/db.php';

try {
    $pdo = get_db_connection();
} catch (Exception $e) {
    die("[Migration] Connection failed: " . $e->getMessage() . "\n");
}

// ── Load content.json ─────────────────────────────────────
$jsonPath = __DIR__ . '/../data/legacy/content.json';
if (!file_exists($jsonPath)) {
    die("[Migration] content.json not found at: $jsonPath\n");
}

$content = json_decode(file_get_contents($jsonPath), true);
if ($content === null) {
    die("[Migration] Failed to parse content.json: " . json_last_error_msg() . "\n");
}

// ── Prepare UPSERT statement ──────────────────────────────
$stmt = $pdo->prepare("
    INSERT INTO content (content_type, content_key, data) 
    VALUES (?, ?, ?) 
    ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
");

$migrated = 0;

// ── Migrate each content section ──────────────────────────
$sections = [
    'myth_busters'       => 'quiz',
    'quiz_questions'     => 'quiz',
    'research_papers'    => 'research',
    'knowledge_articles' => 'knowledge',
    'peer_recognitions'  => 'reviews',
    'photo_wall'         => 'gallery',
];

foreach ($sections as $key => $type) {
    if (!isset($content[$key])) {
        echo "[Skip] '$key' not found in content.json\n";
        continue;
    }

    $data = json_encode($content[$key], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt->execute([$type, $key, $data]);
    $count = is_array($content[$key]) ? count($content[$key]) : 1;
    echo "[OK] Migrated '$key' → content($type, $key) — $count items\n";
    $migrated++;
}

// ── Also migrate settings if they exist ────────────────────────
$settingsPath = __DIR__ . '/../data/legacy/settings.json';
if (file_exists($settingsPath)) {
    $settings = file_get_contents($settingsPath);
    if ($settings) {
        $stmt->execute(['settings', 'site_settings', $settings]);
        echo "[OK] Migrated 'settings.json' → content(settings, site_settings)\n";
        $migrated++;
    }
}

echo "\n✅ Migration complete — $migrated content sections imported.\n";
echo "⚠️  DELETE this file from production after running!\n";
?>
