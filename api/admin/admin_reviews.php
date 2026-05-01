<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/admin_auth.php';

requireAdminAuth();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $reviews = fsGetCollection('reviews');
        $result  = [];

        foreach ($reviews as $r) {
            $bookTitle = '';
            $bookId    = $r['bookId'] ?? '';
            if ($bookId) {
                $book      = fsGetDocument('books', $bookId);
                $bookTitle = $book['title'] ?? '';
            }
            $result[] = [
                'review_id'  => $r['id'],
                'user_name'  => $r['userName']  ?? '',
                'book_title' => $bookTitle,
                'rating'     => $r['rating']    ?? 0,
                'comment'    => $r['comment']   ?? '',
                'created_at' => $r['createdAt'] ?? '',
            ];
        }

        usort($result, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        echo json_encode(['success' => true, 'reviews' => $result]);
        break;

    case 'DELETE':
        $data     = json_decode(file_get_contents('php://input'), true);
        $reviewId = $data['review_id'] ?? '';
        if (!$reviewId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'review_id is required']);
            exit;
        }
        fsDeleteDocument('reviews', $reviewId);
        echo json_encode(['success' => true, 'message' => 'Review deleted']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}