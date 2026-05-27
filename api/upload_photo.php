<?php
/**
 * upload_photo.php — Compressed photo upload for memories & photo wall
 * Accepts base64 image data, re-encodes through GD (max 1200px, WebP),
 * saves to /uploads/memories/ and returns the public URL.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Allow both admin token and public submissions (public uses different path)
$isAdmin = isAdmin();
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST only']); exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$base64Data = $input['photo_data'] ?? '';
$context = $input['context'] ?? 'general'; // 'memories', 'photo_wall'

$allowedContexts = ['memories', 'photo_wall'];
if (!$isAdmin && !in_array($context, $allowedContexts, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid upload context']); exit;
}

if (!$isAdmin && !checkRateLimit($clientIP, 5, 3600, 'public_photo_upload')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many uploads. Please try again later.']); exit;
}

if (empty($base64Data)) {
    echo json_encode(['success' => false, 'error' => 'No image data']); exit;
}

// Validate format
if (!preg_match('/^data:image\/(jpeg|jpg|png|webp|gif);base64,/i', $base64Data, $matches)) {
    echo json_encode(['success' => false, 'error' => 'Invalid image format']); exit;
}

// Decode base64
$b64 = substr($base64Data, strpos($base64Data, ',') + 1);
$imageBytes = base64_decode($b64, true);
if ($imageBytes === false) {
    echo json_encode(['success' => false, 'error' => 'Invalid image data']); exit;
}

// Size limit: 8MB raw
if (strlen($imageBytes) > 8 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Image too large. Max 8MB.']); exit;
}

// Create upload directory
$uploadDir = __DIR__ . '/../uploads/memories/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Process through GD
$srcImg = @imagecreatefromstring($imageBytes);
if (!$srcImg) {
    echo json_encode(['success' => false, 'error' => 'Could not process image']); exit;
}

$origW = imagesx($srcImg);
$origH = imagesy($srcImg);
if ($origW < 1 || $origH < 1 || $origW > 6000 || $origH > 6000) {
    imagedestroy($srcImg);
    echo json_encode(['success' => false, 'error' => 'Invalid image dimensions']); exit;
}

// Resize to max 1200px wide (maintain aspect ratio)
$maxW = 1200;
if ($origW > $maxW) {
    $ratio = $maxW / $origW;
    $newW = $maxW;
    $newH = (int)round($origH * $ratio);
} else {
    $newW = $origW;
    $newH = $origH;
}

$dstImg = imagecreatetruecolor($newW, $newH);
// Handle transparency for PNG
imagealphablending($dstImg, false);
imagesavealpha($dstImg, true);
$transparent = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $transparent);
imagealphablending($dstImg, true);

imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
imagedestroy($srcImg);

// Generate unique filename
$filename = uniqid('photo_', true) . '.webp';
$filepath = $uploadDir . $filename;

// Save as WebP (quality 82 — good balance of size/quality)
$saved = false;
if (function_exists('imagewebp')) {
    $saved = imagewebp($dstImg, $filepath, 82);
}

// Fallback to JPEG if WebP not available
if (!$saved) {
    $filename = uniqid('photo_', true) . '.jpg';
    $filepath = $uploadDir . $filename;
    imagejpeg($dstImg, $filepath, 82);
    $saved = true;
}

imagedestroy($dstImg);

if (!$saved || !file_exists($filepath)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save image']); exit;
}

// Return public URL
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'foxwisdom.com';
$publicUrl = $protocol . '://' . $host . '/uploads/memories/' . $filename;

echo json_encode([
    'success' => true,
    'url' => $publicUrl,
    'filename' => $filename,
    'size_kb' => (int)(filesize($filepath) / 1024)
]);
