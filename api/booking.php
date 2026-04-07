<?php
/**
 * AI Apollo — Booking Handler
 * Saves booking to MySQL + sends notification email via Hostinger SMTP
 * Place in: public_html/api/booking.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// ── CONFIGURATION ─────────────────────────────────────────
$DB_HOST = 'localhost';
$DB_USER = 'your_db_user';        // Replace with Hostinger DB username
$DB_PASS = 'your_db_password';    // Replace with Hostinger DB password
$DB_NAME = 'your_db_name';        // Replace with Hostinger DB name

$SMTP_FROM  = 'bookings@forwisdom.com';    // Your Hostinger email
$NOTIFY_TO  = 'drjaykothari@gmail.com';    // Dr. Kothari's notification email

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

// Sanitize
$name   = htmlspecialchars($name);
$phone  = preg_replace('/[^0-9+\- ]/', '', $phone);
$reason = htmlspecialchars($reason);

// ── DATABASE SAVE ─────────────────────────────────────────
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(200) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        booking_date DATE NOT NULL,
        booking_time VARCHAR(20) NOT NULL,
        reason TEXT,
        status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $pdo->prepare("INSERT INTO bookings (name, email, phone, booking_date, booking_time, reason) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $date, $time, $reason]);
    $bookingId = $pdo->lastInsertId();

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please call us directly.']);
    error_log('Booking DB error: ' . $e->getMessage());
    exit;
}

// ── EMAIL NOTIFICATION TO DR. KOTHARI'S TEAM ─────────────
$subject = "New OPD Booking: $name — $date at $time";
$body = "New booking received via AI Apollo website.\n\n"
    . "Patient Name : $name\n"
    . "Email        : $email\n"
    . "Phone        : $phone\n"
    . "Date         : $date\n"
    . "Time         : $time\n"
    . "Reason       : $reason\n\n"
    . "Booking ID   : #$bookingId\n"
    . "Status       : Pending\n\n"
    . "Log in to admin panel to update status.";

$headers = "From: $SMTP_FROM\r\nReply-To: $email\r\nX-Mailer: PHP/" . phpversion();
mail($NOTIFY_TO, $subject, $body, $headers);

// ── AUTO-REPLY TO PATIENT ─────────────────────────────────
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
mail($email, $patientSubject, $patientBody, $patientHeaders);

// ── RESPONSE ──────────────────────────────────────────────
echo json_encode([
    'success'    => true,
    'message'    => 'Booking submitted successfully.',
    'booking_id' => $bookingId
]);
?>
