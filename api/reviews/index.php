<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/database.php';

$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

if ($book_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "book_id is required"]);
    exit;
}

try {
    $db = getDB(); // ✅ matches your config

    $stmt = $db->prepare("
        SELECT 
            r.review_id,
            r.user_id,
            u.u_name      AS user_name,
            r.r_rating    AS rating,
            r.r_comment   AS comment,
            r.r_created_at AS created_at
        FROM reviews r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.book_id = :book_id AND r.r_status = 'approved'
        ORDER BY r.r_created_at DESC
    ");
    $stmt->execute([':book_id' => $book_id]);
    $reviews = $stmt->fetchAll();

    $avgStmt = $db->prepare("
        SELECT 
            ROUND(AVG(r_rating), 1) AS average_rating,
            COUNT(*) AS review_count
        FROM reviews
        WHERE book_id = :book_id AND r_status = 'approved'
    ");
    $avgStmt->execute([':book_id' => $book_id]);
    $stats = $avgStmt->fetch();

    echo json_encode([
        "success"        => true,
        "average_rating" => $stats['average_rating'] ? floatval($stats['average_rating']) : 0.0,
        "review_count"   => intval($stats['review_count']),
        "reviews"        => $reviews
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}