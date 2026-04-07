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

// This line protects the endpoint — returns user_id from token
$userId = requireAuth();

$data            = json_decode(file_get_contents('php://input'), true);
$items           = $data['items']            ?? [];
$shippingAddress = trim($data['shipping_address'] ?? '');

if (empty($items) || !$shippingAddress) {
    http_response_code(400);
    echo json_encode(['error' => 'Items and shipping address are required']);
    exit;
}

$pdo = getDB();

// Validate each item and calculate total
$total = 0;
$validatedItems = [];

foreach ($items as $item) {
    $bookId   = (int) ($item['book_id']  ?? 0);
    $quantity = (int) ($item['quantity'] ?? 0);

    if (!$bookId || $quantity < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid item data']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT book_id, b_price, b_stock FROM books WHERE book_id = ?');
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();

    if (!$book) {
        http_response_code(404);
        echo json_encode(['error' => "Book ID $bookId not found"]);
        exit;
    }

    if ($book['b_stock'] < $quantity) {
        http_response_code(409);
        echo json_encode(['error' => "Not enough stock for book ID $bookId"]);
        exit;
    }

    $total           += $book['b_price'] * $quantity;
    $validatedItems[] = [
        'book_id'   => $bookId,
        'quantity'  => $quantity,
        'unit_price' => $book['b_price'],
    ];
}

// All good — insert order and items in a transaction
$pdo->beginTransaction();

try {
    // Insert order
    $stmt = $pdo->prepare('INSERT INTO orders (user_id, o_total, o_shipping_address) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $total, $shippingAddress]);
    $orderId = $pdo->lastInsertId();

    // Insert order items + decrement stock
    foreach ($validatedItems as $item) {
        $stmt = $pdo->prepare('INSERT INTO order_items (order_id, book_id, oi_quantity, oi_unit_price) VALUES (?, ?, ?, ?)');
        $stmt->execute([$orderId, $item['book_id'], $item['quantity'], $item['unit_price']]);

        $stmt = $pdo->prepare('UPDATE books SET b_stock = b_stock - ? WHERE book_id = ?');
        $stmt->execute([$item['quantity'], $item['book_id']]);
    }

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'message'  => 'Order placed successfully',
        'order_id' => $orderId,
        'total'    => $total,
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Order failed, please try again']);
}