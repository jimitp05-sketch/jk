<?php
/**
 * AI Apollo — Centralized Authentication
 *
 * Single source of truth for all admin authentication.
 * Uses session tokens instead of sending passwords on every request.
 *
 * Usage in any API file:
 *   require_once __DIR__ . '/auth.php';
 *   requireAdmin();  // Halts with 401 if not authenticated
 *
 *   // Or check without halting:
 *   if (!isAdmin()) { ... }
 */

require_once __DIR__ . '/db.php';

// ── SESSION TOKEN CONFIG ────────────────────────────────────────────────
define('AUTH_TOKEN_EXPIRY', 86400); // 24 hours
define('AUTH_TOKEN_FILE', __DIR__ . '/../data/auth_tokens.json');
define('AUTH_LOCKOUT_FILE', __DIR__ . '/../data/login_attempts.json');
define('AUTH_MAX_ATTEMPTS', 5);
define('AUTH_LOCKOUT_SECONDS', 900); // 15 minutes

// ── READ ADMIN CREDENTIALS FROM DATABASE ────────────────────────────────
function getAdminCredentials(): array {
    $defaults = [
        'admin_user' => 'admin',
        'admin_pass' => password_hash('apollo2024', PASSWORD_DEFAULT)
    ];

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'site_settings' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) return $defaults;

        $saved = json_decode($row['data'], true) ?: [];

        // Migration: hash plaintext passwords
        if (isset($saved['admin_pass']) && !isHashedPassword($saved['admin_pass'])) {
            $saved['admin_pass'] = password_hash($saved['admin_pass'], PASSWORD_DEFAULT);
            // Write back hashed version
            $pdo2 = get_db_connection();
            $allData = $saved;
            $stmt2 = $pdo2->prepare("UPDATE content SET data = ? WHERE content_key = 'site_settings'");
            $stmt2->execute([json_encode($allData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }

        return [
            'admin_user' => $saved['admin_user'] ?? $defaults['admin_user'],
            'admin_pass' => $saved['admin_pass'] ?? $defaults['admin_pass']
        ];
    } catch (Exception $e) {
        error_log('Auth: Failed to read credentials from DB: ' . $e->getMessage());
        return $defaults;
    }
}

function isHashedPassword(string $pass): bool {
    return str_starts_with($pass, '$2y$') || str_starts_with($pass, '$argon2id$') || str_starts_with($pass, '$2a$');
}

// ── PASSWORD VERIFICATION ───────────────────────────────────────────────
function verifyAdminPassword(string $provided): bool {
    $creds = getAdminCredentials();
    $hashedPass = $creds['admin_pass'];

    if (isHashedPassword($hashedPass)) {
        return password_verify($provided, $hashedPass);
    }

    // Refuse to authenticate against unhashed passwords
    error_log('Auth: Refusing plaintext password comparison. Password must be hashed.');
    return false;
}

// ── SESSION TOKEN MANAGEMENT ────────────────────────────────────────────
function generateSessionToken(): string {
    return bin2hex(random_bytes(32));
}

function saveSessionToken(string $token, string $ip): void {
    $tokens = readTokenFile();
    // Clean expired tokens
    $now = time();
    $tokens = array_filter($tokens, fn($t) => ($now - ($t['created'] ?? 0)) < AUTH_TOKEN_EXPIRY);
    // Save new token
    $tokens[$token] = [
        'created' => $now,
        'ip' => $ip,
        'last_used' => $now
    ];
    @file_put_contents(AUTH_TOKEN_FILE, json_encode($tokens), LOCK_EX);
}

function validateSessionToken(string $token): bool {
    if (empty($token) || strlen($token) < 32) return false;

    $tokens = readTokenFile();
    if (!isset($tokens[$token])) return false;

    $entry = $tokens[$token];
    $now = time();

    // Check expiry
    if (($now - ($entry['created'] ?? 0)) > AUTH_TOKEN_EXPIRY) {
        revokeSessionToken($token);
        return false;
    }

    // Update last_used
    $tokens[$token]['last_used'] = $now;
    @file_put_contents(AUTH_TOKEN_FILE, json_encode($tokens), LOCK_EX);

    return true;
}

function revokeSessionToken(string $token): void {
    $tokens = readTokenFile();
    unset($tokens[$token]);
    @file_put_contents(AUTH_TOKEN_FILE, json_encode($tokens), LOCK_EX);
}

function revokeAllSessionTokens(): void {
    @file_put_contents(AUTH_TOKEN_FILE, json_encode([]), LOCK_EX);
}

function readTokenFile(): array {
    if (!file_exists(AUTH_TOKEN_FILE)) return [];
    return json_decode(@file_get_contents(AUTH_TOKEN_FILE), true) ?: [];
}

// ── BRUTE-FORCE PROTECTION ──────────────────────────────────────────────
function checkBruteForce(string $ip): bool {
    if (!file_exists(AUTH_LOCKOUT_FILE)) return true;
    $attempts = json_decode(@file_get_contents(AUTH_LOCKOUT_FILE), true) ?: [];
    $entry = $attempts[$ip] ?? null;
    if (!$entry) return true;
    if ($entry['count'] >= AUTH_MAX_ATTEMPTS && (time() - $entry['last']) < AUTH_LOCKOUT_SECONDS) return false;
    if ((time() - $entry['last']) >= AUTH_LOCKOUT_SECONDS) {
        unset($attempts[$ip]);
        @file_put_contents(AUTH_LOCKOUT_FILE, json_encode($attempts), LOCK_EX);
        return true;
    }
    return true;
}

function recordFailedLogin(string $ip): void {
    $attempts = [];
    if (file_exists(AUTH_LOCKOUT_FILE)) {
        $attempts = json_decode(@file_get_contents(AUTH_LOCKOUT_FILE), true) ?: [];
    }
    $entry = $attempts[$ip] ?? ['count' => 0, 'last' => 0];
    $entry['count']++;
    $entry['last'] = time();
    $attempts[$ip] = $entry;
    @file_put_contents(AUTH_LOCKOUT_FILE, json_encode($attempts), LOCK_EX);
}

function clearFailedLogin(string $ip): void {
    if (!file_exists(AUTH_LOCKOUT_FILE)) return;
    $attempts = json_decode(@file_get_contents(AUTH_LOCKOUT_FILE), true) ?: [];
    unset($attempts[$ip]);
    @file_put_contents(AUTH_LOCKOUT_FILE, json_encode($attempts), LOCK_EX);
}

// ── EXTRACT TOKEN FROM REQUEST ──────────────────────────────────────────
function extractAuthToken(): string {
    // 1. Authorization: Bearer <token> header
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $authHeader, $m)) {
        return trim($m[1]);
    }

    // 2. X-Admin-Token header
    if (!empty($_SERVER['HTTP_X_ADMIN_TOKEN'])) {
        return $_SERVER['HTTP_X_ADMIN_TOKEN'];
    }

    // 3. JSON body (for POST requests)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = getJsonInput();
        if (!empty($input['session_token'])) return $input['session_token'];
    }

    return '';
}

// ── MAIN AUTH CHECK ─────────────────────────────────────────────────────
function isAdmin(): bool {
    $token = extractAuthToken();
    return validateSessionToken($token);
}

function requireAdmin(): void {
    if (!isAdmin()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in.']);
        exit;
    }
}

// ── LOGIN HANDLER ───────────────────────────────────────────────────────
function handleLogin(string $password, string $ip): array {
    if (!checkBruteForce($ip)) {
        return ['success' => false, 'error' => 'Too many failed attempts. Locked for 15 minutes.', 'code' => 429];
    }

    if (!verifyAdminPassword($password)) {
        recordFailedLogin($ip);
        return ['success' => false, 'error' => 'Invalid credentials.', 'code' => 403];
    }

    clearFailedLogin($ip);
    $token = generateSessionToken();
    saveSessionToken($token, $ip);

    $creds = getAdminCredentials();

    return [
        'success' => true,
        'session_token' => $token,
        'user' => $creds['admin_user'],
        'expires_in' => AUTH_TOKEN_EXPIRY
    ];
}

// ── PASSWORD CHANGE ─────────────────────────────────────────────────────
function changeAdminPassword(string $currentPassword, string $newPassword, string $newUsername = ''): array {
    // Verify current password
    if (!verifyAdminPassword($currentPassword)) {
        return ['success' => false, 'error' => 'Current password is incorrect.'];
    }

    // Validate new password
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'error' => 'New password must be at least 6 characters.'];
    }

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'site_settings' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        $settings = $row ? (json_decode($row['data'], true) ?: []) : [];

        $settings['admin_pass'] = password_hash($newPassword, PASSWORD_DEFAULT);
        if (!empty($newUsername)) {
            $settings['admin_user'] = trim($newUsername);
        }

        $stmt2 = $pdo->prepare("
            INSERT INTO content (content_type, content_key, data)
            VALUES ('settings', 'site_settings', ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
        ");
        $stmt2->execute([json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

        // Revoke all existing sessions (force re-login)
        revokeAllSessionTokens();

        return ['success' => true, 'message' => 'Credentials updated. Please log in again.'];
    } catch (Exception $e) {
        error_log('Auth: Failed to change password: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to update credentials. Please try again.'];
    }
}

// ── JSON INPUT HELPER (cached) ──────────────────────────────────────────
function getJsonInput(): array {
    static $cached = null;
    if ($cached !== null) return $cached;
    $raw = file_get_contents('php://input');
    $cached = json_decode($raw, true) ?? [];
    return $cached;
}

// ── CSRF TOKEN ──────────────────────────────────────────────────────────
function generateCSRFToken(): string {
    $token = bin2hex(random_bytes(32));
    $tokenFile = __DIR__ . '/../data/csrf_tokens.json';
    $tokens = [];
    if (file_exists($tokenFile)) {
        $tokens = json_decode(@file_get_contents($tokenFile), true) ?: [];
    }
    $tokens = array_filter($tokens, fn($t) => (time() - $t['created']) < 7200);
    $tokens[$token] = ['created' => time()];
    @file_put_contents($tokenFile, json_encode($tokens), LOCK_EX);
    return $token;
}

function validateCSRFToken(string $token): bool {
    if (empty($token)) return false;
    $tokenFile = __DIR__ . '/../data/csrf_tokens.json';
    if (!file_exists($tokenFile)) return false;
    $tokens = json_decode(@file_get_contents($tokenFile), true) ?: [];
    if (!isset($tokens[$token])) return false;
    if ((time() - $tokens[$token]['created']) > 7200) return false;
    unset($tokens[$token]);
    @file_put_contents($tokenFile, json_encode($tokens), LOCK_EX);
    return true;
}

// ── RATE LIMITING ───────────────────────────────────────────────────────
function checkRateLimit(string $ip, int $max = 10, int $window = 60, string $prefix = 'default'): bool {
    $file = __DIR__ . '/../data/rate_limits.json';
    $limits = [];
    if (file_exists($file)) {
        $limits = json_decode(@file_get_contents($file), true) ?: [];
    }
    $now = time();
    $key = $prefix . '_' . $ip;
    foreach ($limits as $k => $v) {
        if (!is_array($v)) { unset($limits[$k]); continue; }
        $limits[$k] = array_values(array_filter($v, fn($t) => ($now - $t) < $window));
        if (empty($limits[$k])) unset($limits[$k]);
    }
    if (count($limits[$key] ?? []) >= $max) return false;
    $limits[$key][] = $now;
    @file_put_contents($file, json_encode($limits), LOCK_EX);
    return true;
}
