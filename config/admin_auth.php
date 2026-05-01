<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

define('ADMIN_JWT_SECRET', 'your_admin_secret_key_change_this');

function requireAdminAuth() {
    $headers = getallheaders();
    $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!$auth || !str_starts_with($auth, 'Bearer ')) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No token provided']);
        exit;
    }

    $token = substr($auth, 7);

    try {
        $decoded = JWT::decode($token, new Key(ADMIN_JWT_SECRET, 'HS256'));
        return $decoded;
    } catch (Exception $e) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }
}
