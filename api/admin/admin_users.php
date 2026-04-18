<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/admin_auth.php';

requireAdminAuth();

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

   
    case 'GET':
        $status = $_GET['status'] ?? null;

        if ($status) {
            $stmt = $pdo->prepare("
                SELECT user_id, u_name, u_email, status, ban_reason
                FROM users
                WHERE status = ?
                ORDER BY user_id DESC
            ");
            $stmt->execute([$status]);
        } else {
            $stmt = $pdo->query("
                SELECT user_id, u_name, u_email, status, ban_reason
                FROM users
                ORDER BY user_id DESC
            ");
        }

        $users = $stmt->fetchAll();
        echo json_encode(['users' => $users]);
        break;


   
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['user_id'], $data['status'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing user_id or status']);
            exit;
        }

        $userId   = $data['user_id'];
        $newStatus = $data['status'];
        $banReason = $data['ban_reason'] ?? null;

        // 🔒 Validate status
        if (!in_array($newStatus, ['active', 'banned'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status']);
            exit;
        }

        
        $stmt = $pdo->prepare("
            UPDATE users
            SET status = ?,
                ban_reason = CASE 
                    WHEN ? = 'banned' THEN ?
                    ELSE NULL
                END
            WHERE user_id = ?
        ");

        $stmt->execute([
            $newStatus,
            $newStatus,
            $banReason,
            $userId
        ]);

        echo json_encode([
            'message' => 'User status updated',
            'status' => $newStatus
        ]);
        break;


    
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}