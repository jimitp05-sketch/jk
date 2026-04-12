<?php
/**
 * AI Apollo — Secure Image Upload API
 * 
 * Handles replacement of core site images from the admin panel.
 * Updates both the base image (PNG/JPG) and generates a matching WebP
 * version for performance consistency.
 */

require_once 'config.php';

header('Content-Type: application/json');

// 1. Authenticate Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$admin_pass = $_POST['admin_pass'] ?? '';
$expected_pass = getenv('ADMIN_PASS') ?: 'apollo2024';

if ($admin_pass !== $expected_pass) {
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

$tmp_name = $_FILES['image']['tmp_name'];
$file_info = getimagesize($tmp_name);

if (!$file_info) {
    echo json_encode(['success' => false, 'error' => 'Invalid image file']);
    exit;
}

// 4. Move Source File
if (move_uploaded_file($tmp_name, $target_path)) {
    // 5. Generate WebP Synchronously for Site Performance
    // Try to use GD for WebP conversion if supported
    try {
        $img = null;
        switch ($file_info[2]) {
            case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($target_path); break;
            case IMAGETYPE_PNG:  $img = imagecreatefrompng($target_path); break;
            case IMAGETYPE_WEBP: $img = imagecreatefromwebp($target_path); break;
        }

        if ($img) {
            // Keep alpha transparency for PNGs
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
            
            imagewebp($img, $webp_path, 82);
            imagedestroy($img);
            $web_msg = " and WebP fallback sync'd";
        } else {
            $web_msg = " (WebP sync skipped: unsupported format)";
        }
    } catch (Exception $e) {
        $web_msg = " (WebP sync failed: " . $e->getMessage() . ")";
    }

    echo json_encode([
        'success' => true,
        'message' => 'Image ' . $target_filename . ' updated successfully' . $web_msg,
        'path' => $target_filename
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file to ' . $target_filename]);
}
