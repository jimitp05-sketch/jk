<?php
/**
 * AI Apollo — Forgot Password / Password Reset
 *
 * POST action=request_reset  → sends a time-limited reset link via email
 * POST action=reset_password → validates token and sets new password
 * GET  action=validate_token → checks if a reset token is still valid
 *
 * Security:
 * - Tokens are 64-char hex, hashed with SHA-256 before storage
 * - Tokens expire after 1 hour
 * - Single-use (deleted after successful reset)
 * - Generic response on request (doesn't reveal whether email exists)
 * - Rate limited: 3 requests per hour per IP
 */

header('Content-Type: application/json');

$allowed_origins = [
    'https://foxwisdom.com',
    'https://www.foxwisdom.com',
    'https://drjaykothari.in',
    'https://www.drjaykothari.in',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} elseif (php_sapi_name() === 'cli' || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

define('RESET_TOKEN_EXPIRY', 3600); // 1 hour
define('RESET_RATE_LIMIT', 3);
define('RESET_RATE_WINDOW', 3600);

$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

function getResetBaseUrl(): string {
    $base = env('RESET_BASE_URL', '');
    if ($base) return rtrim($base, '/');
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

function ensureResetTokensTable(): void {
    $pdo = get_db_connection();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token_hash CHAR(64) NOT NULL,
            admin_email VARCHAR(200) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            used_at TIMESTAMP NULL DEFAULT NULL,
            INDEX idx_token_hash (token_hash),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function getAdminEmail(): string {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'site_settings' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    if (!$row) return '';
    $settings = json_decode($row['data'], true) ?: [];
    return $settings['admin_email'] ?? env('NOTIFY_TO', '');
}

function respond(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// ── REQUEST RESET ──────────────────────────────────────────────────────────
function handleRequestReset(string $email, string $ip): void {
    if (!checkRateLimit($ip, RESET_RATE_LIMIT, RESET_RATE_WINDOW, 'pwd_reset')) {
        respond(429, ['success' => false, 'error' => 'Too many requests. Try again later.']);
    }

    $email = trim(strtolower($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(400, ['success' => false, 'error' => 'Please enter a valid email address.']);
    }

    // Always respond with success to not reveal whether email exists
    $genericResponse = [
        'success' => true,
        'message' => 'If that email is associated with an admin account, a reset link has been sent.'
    ];

    $adminEmail = getAdminEmail();
    if (!$adminEmail || strtolower($adminEmail) !== $email) {
        respond(200, $genericResponse);
    }

    ensureResetTokensTable();
    $pdo = get_db_connection();

    // Invalidate previous unused tokens
    $pdo->exec("DELETE FROM password_reset_tokens WHERE used_at IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL " . RESET_TOKEN_EXPIRY . " SECOND)");

    // Check for recent unused token (prevent spam)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM password_reset_tokens WHERE admin_email = ? AND used_at IS NULL AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $stmt->execute([$email]);
    if ((int)$stmt->fetchColumn() > 0) {
        respond(200, $genericResponse);
    }

    // Generate token
    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);

    $pdo->prepare("INSERT INTO password_reset_tokens (token_hash, admin_email) VALUES (?, ?)")
        ->execute([$tokenHash, $email]);

    // Build reset link
    $baseUrl = getResetBaseUrl();
    $resetLink = $baseUrl . '/admin.php?reset_token=' . $rawToken;

    // Send email
    $subject = 'Password Reset — Dr. Jay Kothari Admin Panel';
    $body = "Hello,\r\n\r\n"
        . "A password reset was requested for the admin panel.\r\n\r\n"
        . "Click the link below to reset your password (valid for 1 hour):\r\n"
        . $resetLink . "\r\n\r\n"
        . "If you did not request this, ignore this email. The link will expire automatically.\r\n\r\n"
        . "— Dr. Jay Kothari Admin System";

    $headers = "From: " . env('SMTP_FROM', 'bookings@foxwisdom.com') . "\r\n"
        . "Reply-To: " . env('SMTP_FROM', 'bookings@foxwisdom.com') . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "X-Mailer: AI-Apollo-Admin";

    // Queue email for cron processing
    try {
        $pdo->prepare("INSERT INTO email_queue (to_email, subject, body, headers) VALUES (?, ?, ?, ?)")
            ->execute([$email, $subject, $body, $headers]);
    } catch (Exception $e) {
        // Fallback to direct send
        @mail($email, $subject, $body, $headers);
        error_log('ForgotPassword: email_queue insert failed, used mail(): ' . $e->getMessage());
    }

    respond(200, $genericResponse);
}

// ── VALIDATE TOKEN ─────────────────────────────────────────────────────────
function handleValidateToken(string $token): void {
    if (strlen($token) !== 64) {
        respond(400, ['valid' => false, 'error' => 'Invalid token format.']);
    }

    ensureResetTokensTable();
    $pdo = get_db_connection();
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare(
        "SELECT id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
    );
    $stmt->execute([$tokenHash, RESET_TOKEN_EXPIRY]);

    if ($stmt->fetch()) {
        respond(200, ['valid' => true]);
    } else {
        respond(200, ['valid' => false, 'error' => 'This reset link has expired or already been used.']);
    }
}

// ── RESET PASSWORD ─────────────────────────────────────────────────────────
function handleResetPassword(string $token, string $newPassword, string $ip): void {
    if (!checkRateLimit($ip, 5, 300, 'pwd_reset_submit')) {
        respond(429, ['success' => false, 'error' => 'Too many attempts. Try again later.']);
    }

    if (strlen($token) !== 64) {
        respond(400, ['success' => false, 'error' => 'Invalid token.']);
    }

    if (strlen($newPassword) < 12) {
        respond(400, ['success' => false, 'error' => 'Password must be at least 12 characters.']);
    }

    ensureResetTokensTable();
    $pdo = get_db_connection();
    $tokenHash = hash('sha256', $token);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "SELECT id, admin_email FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND) FOR UPDATE"
        );
        $stmt->execute([$tokenHash, RESET_TOKEN_EXPIRY]);
        $row = $stmt->fetch();

        if (!$row) {
            $pdo->rollBack();
            respond(400, ['success' => false, 'error' => 'This reset link has expired or already been used. Please request a new one.']);
        }

        // Mark token as used
        $pdo->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?")
            ->execute([$row['id']]);

        // Update admin password
        $settingsStmt = $pdo->prepare("SELECT data FROM content WHERE content_key = 'site_settings' LIMIT 1");
        $settingsStmt->execute();
        $settingsRow = $settingsStmt->fetch();
        $settings = $settingsRow ? (json_decode($settingsRow['data'], true) ?: []) : [];

        $settings['admin_pass'] = password_hash($newPassword, PASSWORD_DEFAULT);

        $pdo->prepare("
            INSERT INTO content (content_type, content_key, data)
            VALUES ('settings', 'site_settings', ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
        ")->execute([json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

        // Revoke all active sessions
        $pdo->exec("DELETE FROM auth_sessions");

        // Clear any brute-force lockouts
        $pdo->exec("DELETE FROM login_attempts");

        $pdo->commit();

        respond(200, ['success' => true, 'message' => 'Password has been reset. You can now sign in with your new password.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('ForgotPassword: reset failed: ' . $e->getMessage());
        respond(500, ['success' => false, 'error' => 'An error occurred. Please try again.']);
    }
}

// ── ROUTE ──────────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $token = $_GET['token'] ?? '';

    if ($action === 'validate_token' && $token) {
        handleValidateToken($token);
    }

    respond(400, ['error' => 'Invalid request.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';

    if ($action === 'request_reset') {
        handleRequestReset($input['email'] ?? '', $clientIP);
    }

    if ($action === 'reset_password') {
        handleResetPassword($input['token'] ?? '', $input['new_password'] ?? '', $clientIP);
    }

    respond(400, ['error' => 'Invalid action.']);
}

respond(405, ['error' => 'Method not allowed.']);
