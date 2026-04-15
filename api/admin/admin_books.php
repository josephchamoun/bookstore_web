<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/admin_auth.php'; 

requireAdminAuth(); 

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // GET all books
    case 'GET':
        $stmt = $pdo->query('SELECT b.*, c.c_name FROM books b 
                             JOIN categories c ON b.category_id = c.category_id 
                             ORDER BY b.b_title');
        echo json_encode(['books' => $stmt->fetchAll()]);
        break;

    // POST add new book
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO books (category_id, b_title, b_author, b_price, b_stock, b_cover_url) 
                               VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['category_id'],
            $data['b_title'],
            $data['b_author'],
            $data['b_price'],
            $data['b_stock'],
            $data['b_cover_url'] ?? ''
        ]);
        http_response_code(201);
        echo json_encode(['message' => 'Book added', 'book_id' => $pdo->lastInsertId()]);
        break;

    // PUT edit book
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('UPDATE books SET category_id=?, b_title=?, b_author=?, b_price=?, b_stock=?, b_cover_url=? 
                               WHERE book_id=?');
        $stmt->execute([
            $data['category_id'],
            $data['b_title'],
            $data['b_author'],
            $data['b_price'],
            $data['b_stock'],
            $data['b_cover_url'] ?? '',
            $data['book_id']
        ]);
        echo json_encode(['message' => 'Book updated']);
        break;

    // DELETE book
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('DELETE FROM books WHERE book_id = ?');
        $stmt->execute([$data['book_id']]);
        echo json_encode(['message' => 'Book deleted']);
        break;
}