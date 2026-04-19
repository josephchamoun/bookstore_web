<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once '../../config/database.php';
require_once '../../middleware/auth.php';

$user_id = requireAuth();
$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

if ($book_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "book_id is required"]);
    exit;
}

try {
    $db = getDB();

    // Check user is active
    $userStmt = $db->prepare("SELECT status FROM users WHERE user_id = :user_id");
    $userStmt->execute([':user_id' => $user_id]);
    $user = $userStmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        echo json_encode(["success" => true, "eligible" => false, "reason" => "banned"]);
        exit;
    }

    // Check delivered order containing this book
    $orderStmt = $db->prepare("
        SELECT o.order_id
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.user_id  = :user_id
          AND oi.book_id = :book_id
          AND o.o_status = 'delivered'
        LIMIT 1
    ");
    $orderStmt->execute([':user_id' => $user_id, ':book_id' => $book_id]);
    $hasPurchased = $orderStmt->fetch() !== false;

    if (!$hasPurchased) {
        echo json_encode(["success" => true, "eligible" => false, "reason" => "not_purchased"]);
        exit;
    }

    // Check if already reviewed
    $reviewStmt = $db->prepare("
        SELECT review_id FROM reviews
        WHERE user_id = :user_id AND book_id = :book_id
    ");
    $reviewStmt->execute([':user_id' => $user_id, ':book_id' => $book_id]);
    $alreadyReviewed = $reviewStmt->fetch() !== false;

    if ($alreadyReviewed) {
        echo json_encode(["success" => true, "eligible" => false, "reason" => "already_reviewed"]);
        exit;
    }

    echo json_encode(["success" => true, "eligible" => true, "reason" => ""]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}