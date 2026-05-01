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
        $status = $_GET['status'] ?? null;
        $orders = $status
            ? fsQuery('orders', 'status', '=', $status)
            : fsGetCollection('orders');

        foreach ($orders as &$order) {
            $order['order_id']   = $order['id'];
            $order['order_date'] = $order['orderDate'] ?? '';

            // Get user info
            $userId = $order['userId'] ?? '';
            $order['u_name']  = '';
            $order['u_email'] = '';
            if ($userId) {
                $user = fsGetDocument('users', $userId);
                if ($user) {
                    $order['u_name']  = $user['name']  ?? '';
                    $order['u_email'] = $user['email'] ?? '';
                }
            }
        }

        usort($orders, fn($a, $b) => strcmp($b['order_date'], $a['order_date']));
        echo json_encode(['orders' => $orders]);
        break;

    case 'PUT':
        $data      = json_decode(file_get_contents('php://input'), true);
        $orderId   = $data['order_id'] ?? '';
        $newStatus = $data['status']   ?? '';

        if (!$orderId || !$newStatus) {
            http_response_code(400);
            echo json_encode(['error' => 'order_id and status are required']);
            exit;
        }

        $order = fsGetDocument('orders', $orderId);
        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }

        $currentStatus = $order['status'] ?? '';
        $items         = $order['items']  ?? [];

        // Restore stock if cancelling
        if ($newStatus === 'cancelled' && $currentStatus !== 'cancelled') {
            foreach ($items as $item) {
                $bookId   = $item['bookId']   ?? '';
                $quantity = $item['quantity'] ?? 0;
                if (!$bookId) continue;
                $book = fsGetDocument('books', $bookId);
                if ($book) {
                    fsUpdateDocument('books', $bookId, [
                        'stock' => ($book['stock'] ?? 0) + $quantity
                    ]);
                }
            }
        }

        // Deduct stock if un-cancelling
        if ($currentStatus === 'cancelled' && $newStatus !== 'cancelled') {
            foreach ($items as $item) {
                $bookId   = $item['bookId']   ?? '';
                $quantity = $item['quantity'] ?? 0;
                if (!$bookId) continue;
                $book = fsGetDocument('books', $bookId);
                if ($book) {
                    fsUpdateDocument('books', $bookId, [
                        'stock' => max(0, ($book['stock'] ?? 0) - $quantity)
                    ]);
                }
            }
        }

        fsUpdateDocument('orders', $orderId, ['status' => $newStatus]);
        echo json_encode(['message' => 'Order status updated']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}