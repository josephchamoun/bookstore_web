<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once '../../config/database.php';
require_once '../../config/admin_auth.php'; // ✅ matches your admin JWT file

requireAdminAuth(); // ✅ validates admin Bearer token, exits on failure

try {
    $db = getDB(); // ✅ matches your config

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $status = $_GET['status'] ?? null;
        $where  = $status ? "WHERE r.r_status = :status" : "";

        $stmt = $db->prepare("
            SELECT 
                r.review_id,
                r.r_rating    AS rating,
                r.r_comment   AS comment,
                r.r_status    AS status,
                r.r_created_at AS created_at,
                u.u_name      AS user_name,
                b.b_title     AS book_title
            FROM reviews r
            JOIN users u ON r.user_id = u.user_id
            JOIN books b ON r.book_id = b.book_id
            $where
            ORDER BY r.r_created_at DESC
        ");

$status ? $stmt->execute([':status' => $status]) : $stmt->execute();
        echo json_encode(["success" => true, "reviews" => $stmt->fetchAll()]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data      = json_decode(file_get_contents("php://input"), true);
        $review_id = isset($data['review_id']) ? intval($data['review_id']) : 0;
        $action    = isset($data['action'])    ? $data['action']            : '';

        if ($review_id <= 0 || !in_array($action, ['approved', 'rejected'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "review_id and action (approved/rejected) are required"]);
            exit;
        }

        $stmt = $db->prepare("UPDATE reviews SET r_status = :status WHERE review_id = :review_id");
        $stmt->execute([':status' => $action, ':review_id' => $review_id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Review not found"]);
            exit;
        }

        echo json_encode(["success" => true, "message" => "Review " . $action]);

    } else {
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed"]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}