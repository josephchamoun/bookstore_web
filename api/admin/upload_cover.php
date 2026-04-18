<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/admin_auth.php';
requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['cover'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file    = $_FILES['cover'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Only jpg, png, webp allowed']);
    exit;
}

if ($file['size'] > 2 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large (max 2MB)']);
    exit;
}

$uploadDir = __DIR__ . '/../../uploads/books/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$filename = 'cover_' . time() . '_' . rand(100, 999) . '.' . $ext;
$dest     = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['error' => 'Upload failed']);
    exit;
}

$ip  = '192.168.1.7'; 
$url = 'http://' . $ip . '/bookstore_api/uploads/books/' . $filename;
echo json_encode(['url' => $url]);