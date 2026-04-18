<?php
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password =      $data['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT user_id, u_name, u_password_hash, status, ban_reason FROM users WHERE u_email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['u_password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

if ($user['status'] === 'banned') {
    http_response_code(403);
    echo json_encode([
        'error'      => 'banned',
        'ban_reason' => 'Ban Reason: ' . ($user['ban_reason'] ?? 'You have been banned.')
    ]);
    exit;
}

$token = createToken($user['user_id']);

echo json_encode([
    'message' => 'Login successful',
    'token'   => $token,
    'user'    => [
        'user_id' => $user['user_id'],
        'name'    => $user['u_name'],
    ]
]);