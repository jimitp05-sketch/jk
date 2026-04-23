<?php
/**
 * AI Apollo — Configuration
 * 
 * Reads secrets from .env file (production) or falls back to defaults (development).
 * NEVER commit .env to version control — it contains real credentials.
 */

// ── Load .env file if it exists ───────────────────────────
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Remove surrounding quotes if present
        if (preg_match('/^(["\'])(.*)\\1$/', $value, $m)) {
            $value = $m[2];
        }
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// ── Helper: get env with fallback ─────────────────────────
function env(string $key, string $default = ''): string {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// ── Return config array ───────────────────────────────────
return [
    'db_host' => env('DB_HOST', 'localhost'),
    'db_user' => env('DB_USER', 'your_db_user'),
    'db_pass' => env('DB_PASS', 'your_db_password'),
    'db_name' => env('DB_NAME', 'your_db_name'),

    'smtp_from' => env('SMTP_FROM', 'bookings@foxwisdom.com'),
    'notify_to' => env('NOTIFY_TO', 'drjaykothari@gmail.com'),

    // reCAPTCHA v3 (leave empty to disable)
    'recaptcha_secret'   => env('RECAPTCHA_SECRET', ''),
    'recaptcha_site_key' => env('RECAPTCHA_SITE_KEY', ''),
];
?>
