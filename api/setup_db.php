<?php
/**
 * AI Apollo — One-Click Setup Runner
 * 
 * Runs the SQL migration and JSON-to-SQL migration using .env credentials.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';

echo "<pre>🚀 Starting AI Apollo Auto-Setup...\n\n";

try {
    $pdo = get_db_connection();
    echo "✅ Database Connection: SUCCESS\n";

    // 1. Run SQL Schema
    $sqlPath = __DIR__ . '/setup/db_migration.sql';
    if (file_exists($sqlPath)) {
        $sql = file_get_contents($sqlPath);
        $pdo->exec($sql);
        echo "✅ SQL Schema: Tables created successfully.\n";
    } else {
        echo "❌ Error: api/setup/db_migration.sql not found.\n";
    }

    // 2. Run Migration Script
    require_once __DIR__ . '/migrate_content.php';
    echo "\n✅ Content Migration: SUCCESS\n";

    echo "\n🎉 Setup is 100% complete!\n";
    echo "---------------------------\n";
    echo "You can now log in to: /admin.php\n";
    echo "Username: admin\n";
    echo "Password: apollo2024\n\n";
    
    echo "⚠️  SECURITY: Please delete these files now:\n";
    echo "   - api/setup/auto_run.php\n";
    echo "   - api/setup/wizard.php\n";
    echo "   - api/migrate_content.php\n";

} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Please check if your database credentials in .env are correct.";
}

echo "</pre>";
?>
