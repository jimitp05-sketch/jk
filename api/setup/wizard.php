<?php
/**
 * AI Apollo — Automated Setup Wizard
 * 
 * Provides a GUI for the user to:
 * 1. Input MySQL credentials.
 * 2. Save them to .env.
 * 3. Create all required SQL tables.
 * 4. Migrate existing JSON data to SQL.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$step = $_GET['step'] ?? 'welcome';
$error = '';
$success = '';

// Helper: Save to .env
function saveToEnv($data) {
    $envPath = __DIR__ . '/../../.env';
    $content = "# AI Apollo Environment Configuration\n";
    foreach ($data as $k => $v) {
        $content .= "{$k}={$v}\n";
    }
    return file_put_contents($envPath, $content);
}

// Logic for Setup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'configure') {
        $host = $_POST['db_host'] ?? 'localhost';
        $name = $_POST['db_name'] ?? '';
        $user = $_POST['db_user'] ?? '';
        $pass = $_POST['db_pass'] ?? '';

        try {
            // Test Connection
            $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // Save .env
            saveToEnv([
                'DB_HOST' => $host,
                'DB_NAME' => $name,
                'DB_USER' => $user,
                'DB_PASS' => $pass,
                'ADMIN_TOKEN' => bin2hex(random_bytes(16))
            ]);

            // Run SQL Schema
            $sql = file_get_contents(__DIR__ . '/db_migration.sql');
            $pdo->exec($sql);

            // Run Data Migration
            ob_start();
            include __DIR__ . '/../migrate_content.php';
            $migrationOutput = ob_get_clean();

            $success = "Database configured and $migrationOutput";
            $step = 'done';
        } catch (Exception $e) {
            $error = "Setup Failed: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Apollo — Setup Wizard</title>
    <link rel="stylesheet" href="../../styles.css">
    <style>
        body { background: #080C1A; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Manrope', sans-serif; }
        .wizard-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 40px; border-radius: 20px; max-width: 500px; width: 90%; backdrop-filter: blur(20px); }
        h1 { margin-bottom: 20px; font-size: 1.5rem; color: #38ABFF; }
        p { color: rgba(255,255,255,0.7); line-height: 1.6; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-size: 0.8rem; color: #6A7099; }
        input { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; }
        .btn-primary { background: #38ABFF; color: #000; width: 100%; border: none; padding: 14px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.3s; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 171, 255, 0.4); }
        .error { background: rgba(255,0,0,0.1); border: 1px solid rgba(255,0,0,0.2); color: #ff6b6b; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .success { background: rgba(0,255,0,0.1); border: 1px solid rgba(0,255,0,0.2); color: #51cf66; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="wizard-card">
        <?php if ($step === 'welcome'): ?>
            <h1>🚀 Let's Link Your Database</h1>
            <p>I will automatically set up the tables, configure your environment, and migrate your data. You just need to create the database on Hostinger first.</p>
            <a href="?step=form" class="btn-primary" style="text-decoration:none; display:block; text-align:center;">Start Setup →</a>
        <?php elseif ($step === 'form' || $error): ?>
            <h1>🛠️ Enter Hostinger DB Details</h1>
            <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
            <form method="POST" action="?step=configure">
                <div class="form-group">
                    <label>MySQL Host</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" name="db_name" placeholder="u12345678_apollo" required>
                </div>
                <div class="form-group">
                    <label>Database User</label>
                    <input type="text" name="db_user" placeholder="u12345678_admin" required>
                </div>
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_pass" required>
                </div>
                <button type="submit" class="btn-primary">Connect & Configure →</button>
            </form>
        <?php elseif ($step === 'done'): ?>
            <h1>✅ Setup Complete!</h1>
            <div class="success"><?= $success ?></div>
            <p>Your website is now fully database-driven. You can now log in to the admin panel.</p>
            <p style="color:#ff6b6b; font-weight:700;">⚠️ IMPORTANT: Please delete the <code>api/setup/wizard.php</code> file now for security!</p>
            <a href="../../admin.php" class="btn-primary" style="text-decoration:none; display:block; text-align:center;">Open Admin Panel →</a>
        <?php endif; ?>
    </div>
</body>
</html>
