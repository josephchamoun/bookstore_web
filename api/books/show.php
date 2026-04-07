<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Book ID is required']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT b.*, c.c_name FROM books b 
                       JOIN categories c ON b.category_id = c.category_id
                       WHERE b.book_id = ?');
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    echo json_encode(['error' => 'Book not found']);
    exit;
}

echo json_encode(['book' => $book]);