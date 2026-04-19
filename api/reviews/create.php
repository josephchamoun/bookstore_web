<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once '../../config/database.php';
require_once '../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

$user_id = requireAuth(); // returns int or exits with 401

$data    = json_decode(file_get_contents("php://input"), true);
$book_id = isset($data['book_id']) ? intval($data['book_id']) : 0;
$rating  = isset($data['rating'])  ? intval($data['rating'])  : 0;
$comment = isset($data['comment']) ? trim($data['comment'])   : '';

if ($book_id <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "book_id, rating (1–5), and comment are required"]);
    exit;
}

try {
    $db = getDB();

    // ── Guard 1: user must be active (not banned) ─────────────────────────────
    $userStmt = $db->prepare("
        SELECT status FROM users WHERE user_id = :user_id
    ");
    $userStmt->execute([':user_id' => $user_id]);
    $user = $userStmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }

    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Your account is banned and cannot submit reviews"]);
        exit;
    }

    // ── Guard 2: user must have a delivered order containing this book ────────
    $purchaseStmt = $db->prepare("
        SELECT o.order_id
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.user_id   = :user_id
          AND oi.book_id  = :book_id
          AND o.o_status  = 'delivered'
        LIMIT 1
    ");
    $purchaseStmt->execute([
        ':user_id' => $user_id,
        ':book_id' => $book_id
    ]);

    if (!$purchaseStmt->fetch()) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "You can only review books from your delivered orders"]);
        exit;
    }

    // ── Guard 3: book must exist ──────────────────────────────────────────────
    $bookStmt = $db->prepare("SELECT book_id FROM books WHERE book_id = :book_id");
    $bookStmt->execute([':book_id' => $book_id]);
    if (!$bookStmt->fetch()) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Book not found"]);
        exit;
    }

    // ── Insert review ─────────────────────────────────────────────────────────
    $stmt = $db->prepare("
        INSERT INTO reviews (user_id, book_id, r_rating, r_comment)
        VALUES (:user_id, :book_id, :rating, :comment)
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':book_id' => $book_id,
        ':rating'  => $rating,
        ':comment' => $comment
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Review submitted and pending approval"
    ]);

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        // UNIQUE (user_id, book_id) constraint — already reviewed
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "You have already reviewed this book"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
    }
}