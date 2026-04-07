<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $stmt = $pdo->query('SELECT * FROM categories ORDER BY c_name');
        echo json_encode(['categories' => $stmt->fetchAll()]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO categories (c_name) VALUES (?)');
        $stmt->execute([$data['c_name']]);
        http_response_code(201);
        echo json_encode(['message' => 'Category added', 'category_id' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('UPDATE categories SET c_name=? WHERE category_id=?');
        $stmt->execute([$data['c_name'], $data['category_id']]);
        echo json_encode(['message' => 'Category updated']);
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('DELETE FROM categories WHERE category_id = ?');
        $stmt->execute([$data['category_id']]);
        echo json_encode(['message' => 'Category deleted']);
        break;
}