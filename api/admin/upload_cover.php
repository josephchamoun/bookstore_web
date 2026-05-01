<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';
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

$bucket   = FIREBASE_PROJECT_ID . '.appspot.com';
$fileName = 'covers/cover_' . time() . '_' . rand(100, 999) . '.' . $ext;
$token    = getAccessToken();
$encoded  = urlencode($fileName);

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Authorization: Bearer $token\r\nContent-Type: {$file['type']}\r\n",
        'content' => file_get_contents($file['tmp_name']),
        'ignore_errors' => true,
    ]
];

$context = stream_context_create($options);
file_get_contents(
    "https://storage.googleapis.com/upload/storage/v1/b/{$bucket}/o?uploadType=media&name={$encoded}&predefinedAcl=publicRead",
    false,
    $context
);

$url = "https://storage.googleapis.com/{$bucket}/{$fileName}";
echo json_encode(['url' => $url]);