<?php
/**
 * AI Apollo — Booking Handler
 * Saves booking to MySQL + sends notification email via Hostinger SMTP
 * Place in: public_html/api/booking.php
 */

header('Content-Type: application/json');

// ── CORS WHITELIST ───────────────────────────────────────
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── RATE LIMITING ────────────────────────────────────────
function checkBookingRateLimit(string $ip): bool {
    $file = __DIR__ . '/../data/rate_limits.json';
    $limits = [];
    if (file_exists($file)) {
        $limits = json_decode(@file_get_contents($file), true) ?: [];
    }
    $now = time();
    $key = 'booking_' . $ip;
    foreach ($limits as $k => $v) {
        $limits[$k] = array_filter($v, fn($t) => ($now - $t) < 300); // 5 min window
        if (empty($limits[$k])) unset($limits[$k]);
    }
    if (count($limits[$key] ?? []) >= 5) return false; // max 5 bookings per 5 min
    $limits[$key][] = $now;
    @file_put_contents($file, json_encode($limits), LOCK_EX);
    return true;
}

$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkBookingRateLimit($clientIP)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many booking attempts. Please try again later.']);
    exit;
}

// ── CONFIGURATION ─────────────────────────────────────────
$config = require __DIR__ . '/config.php';
$DB_HOST = $config['db_host'];
$DB_USER = $config['db_user'];
$DB_PASS = $config['db_pass'];
$DB_NAME = $config['db_name'];
$SMTP_FROM = $config['smtp_from'];
$NOTIFY_TO = $config['notify_to'];

// ── INPUT VALIDATION ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone   = trim($_POST['phone'] ?? '');
$date    = trim($_POST['date'] ?? '');
$time    = trim($_POST['time'] ?? '');
$reason  = trim($_POST['reason'] ?? '');

if (!$name || !$email || !$phone || !$date || !$time) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
    exit;
}

// ── reCAPTCHA v3 VERIFICATION (optional — activate by adding recaptcha_secret to config.php) ───
$recaptchaSecret = $config['recaptcha_secret'] ?? '';
if ($recaptchaSecret) {
    $recaptchaToken = $_POST['g-recaptcha-response'] ?? '';
    if (!$recaptchaToken) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed. Please try again.']);
        exit;
    }
    $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $verifyData = http_build_query(['secret' => $recaptchaSecret, 'response' => $recaptchaToken, 'remoteip' => $clientIP]);
    $verifyResult = @file_get_contents($verifyUrl . '?' . $verifyData);
    $captchaResponse = json_decode($verifyResult, true);
    if (!($captchaResponse['success'] ?? false) || ($captchaResponse['score'] ?? 0) < 0.5) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bot detected. If you are human, please try again.']);
        exit;
    }
}

// ── FIELD VALIDATION ─────────────────────────────────────────
// Name: 2-100 chars, no HTML
$name = htmlspecialchars(mb_substr($name, 0, 100));
if (mb_strlen($name) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid name (at least 2 characters).']);
    exit;
}

// Phone: Indian format — 10 digits optionally prefixed by +91
$phone = preg_replace('/[^0-9+]/', '', $phone);
if (!preg_match('/^(\+91)?[6-9][0-9]{9}$/', $phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit Indian phone number.']);
    exit;
}

// Date: YYYY-MM-DD, must not be in the past
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
    exit;
}
$dateObj = DateTime::createFromFormat('Y-m-d', $date);
if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date.']);
    exit;
}
$today = new DateTime('today', new DateTimeZone('Asia/Kolkata'));
if ($dateObj < $today) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cannot book a date in the past.']);
    exit;
}

// Time: HH:MM format
if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid time format.']);
    exit;
}

// Reason: max 500 chars
$reason = htmlspecialchars(mb_substr($reason, 0, 500));

// ── DATABASE SAVE ─────────────────────────────────────────
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Check for duplicate booking (same date + time slot)
    $dupCheck = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND booking_time = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')");
    $dupCheck->execute([$date, $time]);
    if ($dupCheck->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This time slot is already booked. Please select a different time.', 'code' => 'DUPLICATE_BOOKING']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO bookings (name, email, phone, booking_date, booking_time, reason) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $date, $time, $reason]);
    $bookingId = $pdo->lastInsertId();

} catch (PDOException $e) {
    // Handle UNIQUE constraint violation gracefully (if db_migration.sql applied)
    if ($e->getCode() === '23000' && strpos($e->getMessage(), 'uk_slot') !== false) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This time slot is already booked. Please select a different time.', 'code' => 'DUPLICATE_BOOKING']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please call us directly.']);
    error_log('Booking DB error: ' . $e->getMessage());
    exit;
}

// ── QUEUE EMAIL: Notification to Dr. Kothari's team ──────
$notifySubject = "New OPD Booking: $name — $date at $time";
$notifyBody = "New booking received via AI Apollo website.\n\n"
    . "Patient Name : $name\n"
    . "Email        : $email\n"
    . "Phone        : $phone\n"
    . "Date         : $date\n"
    . "Time         : $time\n"
    . "Reason       : $reason\n\n"
    . "Booking ID   : #$bookingId\n"
    . "Status       : Pending\n\n"
    . "Log in to admin panel to update status.";
$notifyHeaders = "From: $SMTP_FROM\r\nReply-To: $email\r\nX-Mailer: PHP/" . phpversion();

// ── QUEUE EMAIL: Auto-reply to patient ───────────────────
$patientSubject = "Your OPD Appointment Request — Dr. Jay Kothari, Apollo Hospitals";
$patientBody = "Dear $name,\n\n"
    . "Thank you for booking a consultation with Dr. Jay Kothari.\n\n"
    . "Your Booking Details:\n"
    . "──────────────────────────\n"
    . "Date  : $date\n"
    . "Time  : $time\n"
    . "Venue : Apollo Hospitals International, Ahmedabad\n"
    . "──────────────────────────\n\n"
    . "Our team will confirm your appointment within 2 hours.\n\n"
    . "For emergencies, please call: 1860-500-1066\n\n"
    . "Warm regards,\n"
    . "Dr. Jay Kothari's Office\n"
    . "AI Apollo — Critical Care, Apollo Hospitals, Ahmedabad";
$patientHeaders = "From: $SMTP_FROM\r\nX-Mailer: PHP/" . phpversion();

// Insert into email queue (processed by cron job)
try {
    $queueStmt = $pdo->prepare("INSERT INTO email_queue (to_email, subject, body, headers) VALUES (?, ?, ?, ?)");
    $queueStmt->execute([$NOTIFY_TO, $notifySubject, $notifyBody, $notifyHeaders]);
    $queueStmt->execute([$email, $patientSubject, $patientBody, $patientHeaders]);
} catch (PDOException $e) {
    // Queue failure is non-fatal — booking is already saved
    // Fall back to synchronous mail
    error_log('Email queue insert failed, falling back to mail(): ' . $e->getMessage());
    @mail($NOTIFY_TO, $notifySubject, $notifyBody, $notifyHeaders);
    @mail($email, $patientSubject, $patientBody, $patientHeaders);
}

// ── STANDARDIZED RESPONSE ────────────────────────────────
echo json_encode([
    'success' => true,
    'data'    => [
        'booking_id'   => (int)$bookingId,
        'booking_date' => $date,
        'booking_time' => $time,
        'patient_name' => $name,
    ],
    'error'   => null,
    'meta'    => [
        'timestamp' => date('c'),
    ],
]);
?>
