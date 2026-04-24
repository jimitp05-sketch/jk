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
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// ── GET: Return booked slots for calendar ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $month = $_GET['month'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = date('Y-m');
    }

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT booking_date, booking_time FROM bookings WHERE booking_date LIKE ? AND status IN ('pending', 'confirmed') AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')");
        $stmt->execute([$month . '%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $slots = [];
        foreach ($rows as $row) {
            $slots[$row['booking_date']][] = $row['booking_time'];
        }

        echo json_encode(['success' => true, 'booked_slots' => $slots]);
    } catch (PDOException $e) {
        error_log('Booking slots query error: ' . $e->getMessage());
        echo json_encode(['success' => true, 'booked_slots' => []]);
    }
    exit;
}

// ── RATE LIMITING ────────────────────────────────────────
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkRateLimit($clientIP, 5, 300, 'booking')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many booking attempts. Please try again later.']);
    exit;
}

// ── CONFIGURATION ─────────────────────────────────────────
$config = require __DIR__ . '/config.php';
$SMTP_FROM = $config['smtp_from'];
$NOTIFY_TO = $config['notify_to'];

// ── INPUT PARSING ─────────────────────────────────────────
$input = $_POST;
if (empty($input) || !isset($input['name'])) {
    $rawBody = file_get_contents('php://input');
    $jsonInput = json_decode($rawBody, true);
    if (is_array($jsonInput)) $input = $jsonInput;
}

// ── METHOD CHECK ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ── CSRF VALIDATION ───────────────────────────────────────
$csrfToken = $input['csrf_token'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired form token. Please refresh the page and try again.']);
    exit;
}

// ── INPUT VALIDATION ──────────────────────────────────────
$name    = trim($input['name'] ?? '');
$email   = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone   = trim($input['phone'] ?? '');
$date    = trim($input['preferred_date'] ?? $input['date'] ?? '');
$time    = trim($input['preferred_slot'] ?? $input['time'] ?? '');
$reason  = trim($input['reason'] ?? '');

if (!$name || !$phone || !$date || !$time) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
    exit;
}

// ── reCAPTCHA v3 VERIFICATION ─────────────────────────────
$recaptchaSecret = $config['recaptcha_secret'] ?? '';
if ($recaptchaSecret) {
    $recaptchaToken = $input['g-recaptcha-response'] ?? '';
    if (!$recaptchaToken) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed. Please try again.']);
        exit;
    }

    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['secret' => $recaptchaSecret, 'response' => $recaptchaToken, 'remoteip' => $clientIP]),
        CURLOPT_TIMEOUT        => 5,
    ]);
    $verifyResult = curl_exec($ch);
    $curlError    = curl_error($ch);
    curl_close($ch);

    if ($verifyResult === false) {
        error_log('reCAPTCHA curl error: ' . $curlError);
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'Verification service temporarily unavailable. Please try again.']);
        exit;
    }

    $captchaResponse = json_decode($verifyResult, true);
    if (!($captchaResponse['success'] ?? false) || ($captchaResponse['score'] ?? 0) < 0.5) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bot detected. If you are human, please try again.']);
        exit;
    }
}

// ── FIELD VALIDATION ─────────────────────────────────────────
// Name: 2-100 chars, no HTML
$name = mb_substr($name, 0, 100);
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
$reason = mb_substr($reason, 0, 500);

// ── DATABASE SAVE ─────────────────────────────────────────
try {
    $pdo = get_db_connection();

    $pdo->beginTransaction();

    try {
        // Check for duplicate booking (same date + time slot)
        $dupCheck = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND booking_time = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')");
        $dupCheck->execute([$date, $time]);
        if ($dupCheck->fetchColumn() > 0) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'This time slot is already booked. Please select a different time.', 'code' => 'DUPLICATE_BOOKING']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO bookings (name, email, phone, booking_date, booking_time, reason) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email ?: '', $phone, $date, $time, $reason]);
        $bookingId = $pdo->lastInsertId();

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
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

// Sanitize email for headers (prevent injection)
$safeEmail = $email ? str_replace(["\r", "\n", "%0a", "%0d"], '', $email) : '';

// ── QUEUE EMAIL: Notification to Dr. Kothari's team ──────
$notifySubject = "New OPD Booking: $name — $date at $time";
$notifyBody    = "New booking received via AI Apollo website.\n\n"
    . "Patient Name : $name\n"
    . "Email        : " . ($email ?: 'Not provided') . "\n"
    . "Phone        : $phone\n"
    . "Date         : $date\n"
    . "Time         : $time\n"
    . "Reason       : $reason\n\n"
    . "Booking ID   : #$bookingId\n"
    . "Status       : Pending\n\n"
    . "Log in to admin panel to update status.";
$notifyHeaders = "From: $SMTP_FROM\r\n" . ($safeEmail ? "Reply-To: $safeEmail\r\n" : '') . "X-Mailer: ApolloBookings";

// ── QUEUE EMAIL: Auto-reply to patient ───────────────────
$patientSubject = "Your OPD Appointment Request — Dr. Jay Kothari, Apollo Hospitals";
$patientBody    = "Dear $name,\n\n"
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
$patientHeaders = "From: $SMTP_FROM\r\nX-Mailer: ApolloBookings";

try {
    $queueStmt = $pdo->prepare("INSERT INTO email_queue (to_email, subject, body, headers) VALUES (?, ?, ?, ?)");
    $queueStmt->execute([$NOTIFY_TO, $notifySubject, $notifyBody, $notifyHeaders]);
    if ($safeEmail) {
        $queueStmt->execute([$safeEmail, $patientSubject, $patientBody, $patientHeaders]);
    }
} catch (PDOException $e) {
    // Queue failure is non-fatal — booking is already saved
    error_log('Email queue insert failed, falling back to mail(): ' . $e->getMessage());
    @mail($NOTIFY_TO, $notifySubject, $notifyBody, $notifyHeaders);
    if ($safeEmail) @mail($safeEmail, $patientSubject, $patientBody, $patientHeaders);
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
    'meta'    => ['timestamp' => date('c')],
]);
?>
