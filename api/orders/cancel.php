<?php
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

$userId = requireAuth();

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? null;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order_id']);
    exit;
}

$pdo = getDB(); // 👈 was missing

// Check order exists and belongs to this user
$stmt = $pdo->prepare("SELECT o_status FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

if ($order['o_status'] === 'cancelled') {
    http_response_code(400);
    echo json_encode(['error' => 'Order is already cancelled']);
    exit;
}

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("SELECT book_id, oi_quantity FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $stmt = $pdo->prepare("UPDATE books SET b_stock = b_stock + ? WHERE book_id = ?");
        $stmt->execute([$item['oi_quantity'], $item['book_id']]);
    }

    $stmt = $pdo->prepare("UPDATE orders SET o_status = 'cancelled' WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);

    $pdo->commit();
    echo json_encode(['message' => 'Order cancelled successfully']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Cancellation failed, please try again']);
}