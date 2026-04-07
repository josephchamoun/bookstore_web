<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$userId = requireAuth();

$pdo  = getDB();

// Fetch all orders for this user
$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC');
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// Attach items to each order
foreach ($orders as &$order) {
    $stmt = $pdo->prepare('SELECT oi.*, b.b_title, b.b_author, b.b_cover_url 
                           FROM order_items oi
                           JOIN books b ON oi.book_id = b.book_id
                           WHERE oi.order_id = ?');
    $stmt->execute([$order['order_id']]);
    $order['items'] = $stmt->fetchAll();
}

echo json_encode(['orders' => $orders]);