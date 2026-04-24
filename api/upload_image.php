<?php
/**
 * AI Apollo — Secure Image Upload API
 *
 * Handles replacement of core site images from the admin panel.
 * Updates both the base image (PNG/JPG) and generates a matching WebP
 * version for performance consistency.
 */

require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

// CORS whitelist
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
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Authenticate using session token from header or POST body
$token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? $_POST['session_token'] ?? '';
if (!validateSessionToken($token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication failed']);
    exit;
}

// 2. Validate Type & Mapping
$type = $_POST['type'] ?? '';
$mapping = [
    'hero'      => 'img-hero-doctor.png',
    'ecmo'      => 'img-ecmo.png',
    'team'      => 'img-team.png',
    'knowledge' => 'img-knowledge.png'
];

if (!isset($mapping[$type])) {
    echo json_encode(['success' => false, 'error' => 'Invalid image category']);
    exit;
}

$target_filename = $mapping[$type];
$target_path = __DIR__ . '/../' . $target_filename;
$webp_path = __DIR__ . '/../' . str_replace('.png', '.webp', $target_filename);

// 3. Handle File Upload
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Upload error: ' . ($_FILES['image']['error'] ?? 'No file')]);
    exit;
}

// File size limit: 10MB
if ($_FILES['image']['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Image too large. Maximum 10MB.']);
    exit;
}

$tmp_name = $_FILES['image']['tmp_name'];
$file_info = getimagesize($tmp_name);

if (!$file_info) {
    echo json_encode(['success' => false, 'error' => 'Invalid image file']);
    exit;
}

$allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
if (!in_array($file_info[2], $allowedTypes, true)) {
    echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, and WebP images are allowed.']);
    exit;
}

// 4. Move uploaded file to temp location, then re-encode through GD
$temp_path = $target_path . '.tmp';
if (move_uploaded_file($tmp_name, $temp_path)) {
    // 5. Re-encode image through GD to strip embedded payloads (polyglot prevention)
    try {
        $img = null;
        switch ($file_info[2]) {
            case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($temp_path); break;
            case IMAGETYPE_PNG:  $img = imagecreatefrompng($temp_path); break;
            case IMAGETYPE_WEBP: $img = imagecreatefromwebp($temp_path); break;
        }

        if ($img) {
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);

            // Re-encode the original as clean PNG
            imagepng($img, $target_path, 6);
            // Generate WebP copy
            imagewebp($img, $webp_path, 82);
            imagedestroy($img);
            @unlink($temp_path);
            $web_msg = " and WebP fallback sync'd";
        } else {
            rename($temp_path, $target_path);
            $web_msg = " (WebP sync skipped: unsupported format)";
        }
    } catch (Exception $e) {
        if (file_exists($temp_path)) rename($temp_path, $target_path);
        $web_msg = " (WebP sync failed: " . $e->getMessage() . ")";
    }

    echo json_encode([
        'success' => true,
        'message' => 'Image ' . $target_filename . ' updated successfully' . $web_msg,
        'path' => $target_filename
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save uploaded image. Please try again.']);
}
