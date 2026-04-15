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
            $stmt = $pdo->prepare('SELECT o.*, u.u_name, u.u_email FROM orders o 
                                   JOIN users u ON o.user_id = u.user_id 
                                   WHERE o.o_status = ? 
                                   ORDER BY o.order_date DESC');
            $stmt->execute([$status]);
        } else {
            $stmt = $pdo->query('SELECT o.*, u.u_name, u.u_email FROM orders o 
                                 JOIN users u ON o.user_id = u.user_id 
                                 ORDER BY o.order_date DESC');
        }
        $orders = $stmt->fetchAll();

        // Attach items to each order
        foreach ($orders as &$order) {
            $s = $pdo->prepare('SELECT oi.*, b.b_title FROM order_items oi 
                                JOIN books b ON oi.book_id = b.book_id 
                                WHERE oi.order_id = ?');
            $s->execute([$order['order_id']]);
            $order['items'] = $s->fetchAll();
        }

        echo json_encode(['orders' => $orders]);
        break;

    case 'PUT':
        $data      = json_decode(file_get_contents('php://input'), true);
        $orderId   = $data['order_id'];
        $newStatus = $data['status'];

        // Get the current status before updating
        $stmt = $pdo->prepare('SELECT o_status FROM orders WHERE order_id = ?');
        $stmt->execute([$orderId]);
        $current = $stmt->fetch();

        // Restore stock only if transitioning TO cancelled (not already cancelled)
        if ($newStatus === 'cancelled' && $current['o_status'] !== 'cancelled') {
            $items = $pdo->prepare('SELECT book_id, oi_quantity FROM order_items WHERE order_id = ?');
            $items->execute([$orderId]);
            foreach ($items->fetchAll() as $item) {
                $restore = $pdo->prepare('UPDATE books SET b_stock = b_stock + ? WHERE book_id = ?');
                $restore->execute([$item['oi_quantity'], $item['book_id']]);
            }
        }

        // If un-cancelling (e.g. cancelled → pending), deduct stock again
        if ($current['o_status'] === 'cancelled' && $newStatus !== 'cancelled') {
            $items = $pdo->prepare('SELECT book_id, oi_quantity FROM order_items WHERE order_id = ?');
            $items->execute([$orderId]);
            foreach ($items->fetchAll() as $item) {
                $restore = $pdo->prepare('UPDATE books SET b_stock = b_stock - ? WHERE book_id = ?');
                $restore->execute([$item['oi_quantity'], $item['book_id']]);
            }
        }

        $stmt = $pdo->prepare('UPDATE orders SET o_status = ? WHERE order_id = ?');
        $stmt->execute([$newStatus, $orderId]);
        echo json_encode(['message' => 'Order status updated']);
        break;
}