<?php
/**
 * API: Blog Image Upload
 * POST /api/blog-upload.php
 * Accepts: multipart/form-data with 'image' file
 * Query params: type=feature|content|author
 * Returns: JSON with image URL
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();
require_once __DIR__ . '/admin-auth.php';
requireAdminAuth();

$type   = $_GET['type'] ?? 'content';
$validTypes = ['feature', 'content', 'author'];
if (!in_array($type, $validTypes)) $type = 'content';

if (empty($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'No file uploaded']);
    exit;
}

$file    = $_FILES['image'];
$maxSize = 5 * 1024 * 1024; // 5MB

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Upload error code: '.$file['error']]);
    exit;
}

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'File too large. Maximum 5MB allowed.']);
    exit;
}

// Validate mime type
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimes = ['image/jpeg', 'image/jpg', 'image/webp', 'image/png', 'image/gif'];
if (!in_array($mimeType, $allowedMimes)) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Invalid file type. Allowed: JPG, WebP, PNG, GIF.']);
    exit;
}

// Extension mapping
$extMap = [
    'image/jpeg' => 'jpg',
    'image/jpg'  => 'jpg',
    'image/webp' => 'webp',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
];
$ext = $extMap[$mimeType] ?? 'jpg';

// Build upload path
$year  = date('Y');
$month = date('m');
$uploadDir = __DIR__ . '/../assets/blog-uploads/' . $year . '/' . $month . '/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['status'=>'error','message'=>'Failed to create upload directory']);
        exit;
    }
}

// Sanitize original filename for slug
$origName = pathinfo($file['name'], PATHINFO_FILENAME);
$origName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $origName);
$origName = substr(strtolower($origName), 0, 50);

// Generate unique filename
$filename    = $type . '-' . $origName . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
$destination = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Failed to save uploaded file']);
    exit;
}

// Return relative URL (from site root)
$relativeUrl = '/assets/blog-uploads/' . $year . '/' . $month . '/' . $filename;

echo json_encode([
    'status'   => 'success',
    'message'  => 'Image uploaded successfully',
    'data'     => [
        'url'      => $relativeUrl,
        'filename' => $filename,
        'type'     => $type,
        'size'     => $file['size'],
        'mime'     => $mimeType
    ],
    // TinyMCE expects this format:
    'location' => $relativeUrl
]);
