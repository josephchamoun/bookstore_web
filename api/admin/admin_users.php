<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/admin_auth.php';

requireAdminAuth();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $docs  = fsGetCollection('users');
        $users = [];
        foreach ($docs as $doc) {
            $users[] = [
                'user_id'    => $doc['id'],
                'u_name'     => $doc['name']       ?? '',
                'u_email'    => $doc['email']      ?? '',
                'status'     => $doc['status']     ?? 'active',
                'ban_reason' => $doc['ban_reason'] ?? '',
            ];
        }
        echo json_encode(['users' => $users]);
        break;

    case 'PUT':
        $data      = json_decode(file_get_contents('php://input'), true);
        $userId    = $data['user_id']    ?? '';
        $newStatus = $data['status']     ?? '';
        $banReason = $data['ban_reason'] ?? '';

        if (!$userId || !in_array($newStatus, ['active', 'banned'])) {
            http_response_code(400);
            echo json_encode(['error' => 'user_id and valid status required']);
            exit;
        }

        fsUpdateDocument('users', $userId, [
            'status'     => $newStatus,
            'ban_reason' => $newStatus === 'banned' ? $banReason : '',
        ]);
        echo json_encode(['message' => 'User status updated', 'status' => $newStatus]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}