<?php
/**
 * AI Apollo — Centralized Authentication
 *
 * Single source of truth for all admin authentication.
 * Uses MySQL-backed session tokens instead of flat JSON files.
 *
 * Usage in any API file:
 *   require_once __DIR__ . '/auth.php';
 *   requireAdmin();  // Halts with 401 if not authenticated
 *
 *   // Or check without halting:
 *   if (!isAdmin()) { ... }
 */

require_once __DIR__ . '/db.php';

define('AUTH_TOKEN_EXPIRY', 86400); // 24 hours
define('AUTH_MAX_ATTEMPTS', 5);
define('AUTH_LOCKOUT_SECONDS', 900); // 15 minutes

// ── READ ADMIN CREDENTIALS FROM DATABASE ────────────────────────────────
function getAdminCredentials(): array {
    $defaults = [
        'admin_user' => 'admin',
        'admin_pass' => password_hash('admin', PASSWORD_DEFAULT) // TODO: change via Settings panel
    ];

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'site_settings' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) return $defaults;

        $saved = json_decode($row['data'], true) ?: [];

        // Migration: hash plaintext passwords on first encounter
        if (isset($saved['admin_pass']) && !isHashedPassword($saved['admin_pass'])) {
            $saved['admin_pass'] = password_hash($saved['admin_pass'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE content SET data = ? WHERE content_key = 'site_settings'")
                ->execute([json_encode($saved, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
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

    error_log('Auth: Refusing plaintext password comparison. Password must be hashed.');
    return false;
}

// ── SESSION TOKEN MANAGEMENT (MySQL) ────────────────────────────────────
function generateSessionToken(): string {
    return bin2hex(random_bytes(32));
}

function saveSessionToken(string $token, string $ip): void {
    try {
        $pdo = get_db_connection();
        $pdo->prepare("DELETE FROM auth_sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)")
            ->execute([AUTH_TOKEN_EXPIRY]);
        $pdo->prepare("INSERT IGNORE INTO auth_sessions (token, ip) VALUES (?, ?)")
            ->execute([$token, $ip]);
    } catch (Exception $e) {
        error_log('Auth: saveSessionToken failed: ' . $e->getMessage());
    }
}

function validateSessionToken(string $token): bool {
    if (empty($token) || strlen($token) < 32) return false;
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare(
            "SELECT token FROM auth_sessions WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
        );
        $stmt->execute([$token, AUTH_TOKEN_EXPIRY]);
        if (!$stmt->fetch()) return false;
        $pdo->prepare("UPDATE auth_sessions SET last_used = NOW() WHERE token = ?")->execute([$token]);
        return true;
    } catch (Exception $e) {
        error_log('Auth: validateSessionToken failed: ' . $e->getMessage());
        return false;
    }
}

function revokeSessionToken(string $token): void {
    try {
        $pdo = get_db_connection();
        $pdo->prepare("DELETE FROM auth_sessions WHERE token = ?")->execute([$token]);
    } catch (Exception $e) {
        error_log('Auth: revokeSessionToken failed: ' . $e->getMessage());
    }
}

function revokeAllSessionTokens(): void {
    try {
        $pdo = get_db_connection();
        $pdo->exec("DELETE FROM auth_sessions");
    } catch (Exception $e) {
        error_log('Auth: revokeAllSessionTokens failed: ' . $e->getMessage());
    }
}

// ── BRUTE-FORCE PROTECTION (MySQL) ──────────────────────────────────────
function checkBruteForce(string $ip): bool {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT count, last_attempt FROM login_attempts WHERE ip = ?");
        $stmt->execute([$ip]);
        $row = $stmt->fetch();
        if (!$row) return true;

        if ($row['count'] >= AUTH_MAX_ATTEMPTS) {
            if ((time() - strtotime($row['last_attempt'])) < AUTH_LOCKOUT_SECONDS) return false;
            // Lockout expired — reset
            $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
        }
        return true;
    } catch (Exception $e) {
        error_log('Auth: checkBruteForce failed: ' . $e->getMessage());
        return false; // Fail closed — block login attempts when DB is unavailable
    }
}

function recordFailedLogin(string $ip): void {
    try {
        $pdo = get_db_connection();
        $pdo->prepare(
            "INSERT INTO login_attempts (ip, count, last_attempt) VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE count = count + 1, last_attempt = NOW()"
        )->execute([$ip]);
    } catch (Exception $e) {
        error_log('Auth: recordFailedLogin failed: ' . $e->getMessage());
    }
}

function clearFailedLogin(string $ip): void {
    try {
        $pdo = get_db_connection();
        $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
    } catch (Exception $e) {
        error_log('Auth: clearFailedLogin failed: ' . $e->getMessage());
    }
}

// ── EXTRACT TOKEN FROM REQUEST ──────────────────────────────────────────
function extractAuthToken(): string {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $authHeader, $m)) {
        return trim($m[1]);
    }
    if (!empty($_SERVER['HTTP_X_ADMIN_TOKEN'])) {
        return $_SERVER['HTTP_X_ADMIN_TOKEN'];
    }
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
        'success'      => true,
        'session_token' => $token,
        'user'         => $creds['admin_user'],
        'expires_in'   => AUTH_TOKEN_EXPIRY
    ];
}

// ── PASSWORD CHANGE ─────────────────────────────────────────────────────
function changeAdminPassword(string $currentPassword, string $newPassword, string $newUsername = ''): array {
    if (!verifyAdminPassword($currentPassword)) {
        return ['success' => false, 'error' => 'Current password is incorrect.'];
    }

    if (strlen($newPassword) < 12) {
        return ['success' => false, 'error' => 'New password must be at least 12 characters.'];
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

        $pdo->prepare("
            INSERT INTO content (content_type, content_key, data)
            VALUES ('settings', 'site_settings', ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
        ")->execute([json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

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

// ── CSRF TOKEN (MySQL) ──────────────────────────────────────────────────
function generateCSRFToken(): string {
    $token = bin2hex(random_bytes(32));
    try {
        $pdo = get_db_connection();
        $pdo->prepare("DELETE FROM csrf_tokens WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)")->execute();
        $pdo->prepare("INSERT INTO csrf_tokens (token) VALUES (?)")->execute([$token]);
    } catch (Exception $e) {
        error_log('Auth: generateCSRFToken failed: ' . $e->getMessage());
    }
    return $token;
}

function validateCSRFToken(string $token): bool {
    if (empty($token)) return false;
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare(
            "SELECT token FROM csrf_tokens WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)"
        );
        $stmt->execute([$token]);
        if (!$stmt->fetch()) return false;
        $pdo->prepare("DELETE FROM csrf_tokens WHERE token = ?")->execute([$token]); // one-time use
        return true;
    } catch (Exception $e) {
        error_log('Auth: validateCSRFToken failed: ' . $e->getMessage());
        return false; // Fail closed — reject requests when DB is unavailable
    }
}

// ── RATE LIMITING (MySQL) ───────────────────────────────────────────────
function checkRateLimit(string $ip, int $max = 10, int $window = 60, string $prefix = 'default'): bool {
    try {
        $pdo = get_db_connection();
        // Probabilistic global cleanup (~1% of calls) to prevent unbounded growth
        if (mt_rand(1, 100) === 1) {
            $pdo->exec("DELETE FROM rate_limits WHERE hit_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        }
        $key = $prefix . '_' . $ip;
        $pdo->prepare("DELETE FROM rate_limits WHERE prefix_ip = ? AND hit_at < DATE_SUB(NOW(), INTERVAL ? SECOND)")
            ->execute([$key, $window]);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rate_limits WHERE prefix_ip = ?");
        $stmt->execute([$key]);
        if ((int)$stmt->fetchColumn() >= $max) return false;
        $pdo->prepare("INSERT INTO rate_limits (prefix_ip) VALUES (?)")->execute([$key]);
        return true;
    } catch (Exception $e) {
        error_log('Auth: checkRateLimit failed: ' . $e->getMessage());
        return false; // Fail closed — reject requests when DB is unavailable
    }
}
