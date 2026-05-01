<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/admin_auth.php';

requireAdminAuth();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $categories = fsGetCollection('categories');
        foreach ($categories as &$c) $c['category_id'] = $c['id'];
        echo json_encode(['categories' => $categories]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['c_name'] ?? '');
        if (!$name) {
            http_response_code(400);
            echo json_encode(['error' => 'c_name is required']);
            exit;
        }
        $id = fsAddDocument('categories', ['name' => $name]);
        http_response_code(201);
        echo json_encode(['message' => 'Category added', 'category_id' => $id]);
        break;

    case 'PUT':
        $data       = json_decode(file_get_contents('php://input'), true);
        $categoryId = $data['category_id'] ?? '';
        $name       = trim($data['c_name'] ?? '');
        if (!$categoryId || !$name) {
            http_response_code(400);
            echo json_encode(['error' => 'category_id and c_name are required']);
            exit;
        }
        fsUpdateDocument('categories', $categoryId, ['name' => $name]);
        echo json_encode(['message' => 'Category updated']);
        break;

    case 'DELETE':
        $data       = json_decode(file_get_contents('php://input'), true);
        $categoryId = $data['category_id'] ?? '';
        if (!$categoryId) {
            http_response_code(400);
            echo json_encode(['error' => 'category_id is required']);
            exit;
        }
        fsDeleteDocument('categories', $categoryId);
        echo json_encode(['message' => 'Category deleted']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}