<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start(); // remove this if you want pure JWT, keep if you still want sessions for HTML pages

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // firebase/php-jwt already installed

use Firebase\JWT\JWT;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password =      $data['password'] ?? '';

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password required']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM admins WHERE a_username = ?');
$stmt->execute([$username]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['a_password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

$secret  = 'your_admin_secret_key_change_this'; // use a long random string
$payload = [
    'admin_id' => $admin['admin_id'],
    'username' => $admin['a_username'],
    'iat'      => time(),
    'exp'      => time() + (60 * 60 * 8) // 8 hours
];

$token = JWT::encode($payload, $secret, 'HS256');

echo json_encode([
    'message' => 'Login successful',
    'token'   => $token
]);