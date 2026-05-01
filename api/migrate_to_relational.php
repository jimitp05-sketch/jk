<?php
/**
 * ONE-TIME MIGRATION: JSON blobs → relational tables
 *
 * Moves diyas, healing_stories, gratitude_notes, memory_photos
 * from the `content` table (JSON) to their own tables.
 *
 * Safe to run multiple times (uses INSERT IGNORE).
 *
 * Usage:
 *   Visit: /api/migrate_to_relational.php?secret=apollo_rel_migrate_2026
 *   Or CLI: php api/migrate_to_relational.php
 *
 * ⚠️ DELETE THIS FILE AFTER SUCCESSFUL MIGRATION
 */

if (php_sapi_name() !== 'cli') {
    if (($_GET['secret'] ?? '') !== 'apollo_rel_migrate_2026') {
        http_response_code(403);
        die('Forbidden');
    }
}

require_once __DIR__ . '/db.php';

echo "<pre style='font-family:monospace;background:#111;color:#0f0;padding:20px;'>\n";
echo "=== JSON → Relational Migration ===\n\n";

try {
    $pdo = get_db_connection();

    // 1. Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS diyas (
            id VARCHAR(30) NOT NULL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            prayer VARCHAR(500) DEFAULT '',
            lit_by VARCHAR(100) DEFAULT '',
            lit_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status ENUM('approved','pending','rejected') DEFAULT 'approved',
            ip_hash CHAR(64) DEFAULT '',
            INDEX idx_status (status),
            INDEX idx_lit_at (lit_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS healing_stories (
            id VARCHAR(40) NOT NULL PRIMARY KEY,
            patient_name VARCHAR(100) DEFAULT '',
            family_name VARCHAR(100) DEFAULT '',
            relationship VARCHAR(50) DEFAULT '',
            duration VARCHAR(50) DEFAULT '',
            tag VARCHAR(50) DEFAULT '',
            title VARCHAR(200) DEFAULT '',
            story TEXT,
            quote VARCHAR(500) DEFAULT '',
            status ENUM('approved','pending','rejected') DEFAULT 'pending',
            submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_hash CHAR(64) DEFAULT '',
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS gratitude_notes (
            id VARCHAR(40) NOT NULL PRIMARY KEY,
            name VARCHAR(100) DEFAULT '',
            note TEXT,
            relationship VARCHAR(50) DEFAULT '',
            status ENUM('approved','pending','rejected') DEFAULT 'pending',
            submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_hash CHAR(64) DEFAULT '',
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS memory_photos (
            id VARCHAR(40) NOT NULL PRIMARY KEY,
            caption VARCHAR(300) DEFAULT '',
            label VARCHAR(50) DEFAULT '',
            uploaded_by VARCHAR(100) DEFAULT '',
            photo_data LONGTEXT,
            status ENUM('approved','pending','rejected') DEFAULT 'pending',
            submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_hash CHAR(64) DEFAULT '',
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Tables created\n";

    // 2. Migrate diyas
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'diyas' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    $diyas = $row ? (json_decode($row['data'], true) ?: []) : [];
    $migrated = 0;
    $insert = $pdo->prepare("INSERT IGNORE INTO diyas (id, name, prayer, lit_by, lit_at, status, ip_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($diyas as $d) {
        $insert->execute([
            $d['id'] ?? 'diya_' . bin2hex(random_bytes(8)),
            $d['name'] ?? '',
            $d['prayer'] ?? '',
            $d['lit_by'] ?? '',
            $d['lit_at'] ?? date('Y-m-d H:i:s'),
            $d['status'] ?? 'approved',
            $d['ip_hash'] ?? ''
        ]);
        $migrated++;
    }
    echo "✅ Diyas: $migrated items migrated\n";

    // 3. Migrate healing_stories
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'healing_stories' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    $items = $row ? (json_decode($row['data'], true) ?: []) : [];
    $migrated = 0;
    $insert = $pdo->prepare("INSERT IGNORE INTO healing_stories (id,patient_name,family_name,relationship,duration,tag,title,story,quote,status,submitted_at,ip_hash) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach ($items as $i) {
        $insert->execute([
            $i['id'] ?? 'healing_stories_' . bin2hex(random_bytes(8)),
            $i['patient_name'] ?? '', $i['family_name'] ?? '', $i['relationship'] ?? '',
            $i['duration'] ?? '', $i['tag'] ?? '', $i['title'] ?? '',
            $i['story'] ?? '', $i['quote'] ?? '',
            $i['status'] ?? 'pending', $i['submitted_at'] ?? date('Y-m-d H:i:s'),
            $i['ip_hash'] ?? ''
        ]);
        $migrated++;
    }
    echo "✅ Healing stories: $migrated items migrated\n";

    // 4. Migrate gratitude_notes
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'gratitude_notes' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    $items = $row ? (json_decode($row['data'], true) ?: []) : [];
    $migrated = 0;
    $insert = $pdo->prepare("INSERT IGNORE INTO gratitude_notes (id,name,note,relationship,status,submitted_at,ip_hash) VALUES (?,?,?,?,?,?,?)");
    foreach ($items as $i) {
        $insert->execute([
            $i['id'] ?? 'gratitude_notes_' . bin2hex(random_bytes(8)),
            $i['name'] ?? '', $i['note'] ?? '', $i['relationship'] ?? '',
            $i['status'] ?? 'pending', $i['submitted_at'] ?? date('Y-m-d H:i:s'),
            $i['ip_hash'] ?? ''
        ]);
        $migrated++;
    }
    echo "✅ Gratitude notes: $migrated items migrated\n";

    // 5. Migrate memory_photos
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'memory_photos' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    $items = $row ? (json_decode($row['data'], true) ?: []) : [];
    $migrated = 0;
    $insert = $pdo->prepare("INSERT IGNORE INTO memory_photos (id,caption,label,uploaded_by,photo_data,status,submitted_at,ip_hash) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($items as $i) {
        $insert->execute([
            $i['id'] ?? 'memory_photos_' . bin2hex(random_bytes(8)),
            $i['caption'] ?? '', $i['label'] ?? '', $i['uploaded_by'] ?? '',
            $i['photo_data'] ?? '',
            $i['status'] ?? 'pending', $i['submitted_at'] ?? date('Y-m-d H:i:s'),
            $i['ip_hash'] ?? ''
        ]);
        $migrated++;
    }
    echo "✅ Memory photos: $migrated items migrated\n";

    echo "\n🎉 Migration complete!\n";
    echo "⚠️  DELETE THIS FILE from production now.\n";
    echo "Old JSON data in 'content' table is preserved as backup.\n";

} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "\n";
}
echo "</pre>";
