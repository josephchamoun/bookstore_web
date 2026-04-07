<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

define('JWT_SECRET', 'your_super_secret_key_change_this');  // change this!

function createToken(int $userId): string {
    $payload = [
        'iat'     => time(),
        'exp'     => time() + (60 * 60 * 24 * 7), // 7 days
        'user_id' => $userId
    ];
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

function requireAuth(): int {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (!str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit;
    }

    $token = substr($authHeader, 7);

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        return $decoded->user_id;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }
}