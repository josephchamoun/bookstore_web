<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// ── JWT check ─────────────────────────────────────────────────────────────────
$user_id = requireAuth();

$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

if ($book_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'book_id is required']);
    exit;
}

try {
    $db = getDB();

    // ── Guard 1: user must be active ──────────────────────────────────────────
    $userStmt = $db->prepare('SELECT status FROM users WHERE user_id = :user_id');
    $userStmt->execute([':user_id' => $user_id]);
    $user = $userStmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Your account is banned']);
        exit;
    }

    // ── Guard 2: book must exist and have a PDF ───────────────────────────────
    $bookStmt = $db->prepare('
        SELECT b_title, b_pdf_url 
        FROM books 
        WHERE book_id = :book_id
    ');
    $bookStmt->execute([':book_id' => $book_id]);
    $book = $bookStmt->fetch();

    if (!$book) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit;
    }

    if (empty($book['b_pdf_url'])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'This book has no ebook available']);
        exit;
    }

    // ── Guard 3: user must have a delivered order containing this book ────────
    $orderStmt = $db->prepare('
        SELECT o.order_id
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.user_id  = :user_id
          AND oi.book_id = :book_id
          AND o.o_status = \'delivered\'
        LIMIT 1
    ');
    $orderStmt->execute([
        ':user_id' => $user_id,
        ':book_id' => $book_id
    ]);

    if (!$orderStmt->fetch()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'You can only read ebooks from your delivered orders'
        ]);
        exit;
    }

    // ── All guards passed — return the PDF URL ────────────────────────────────
    // Build full URL Android can download from
    $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'];
    $basePath  = '/bookstore_api/';
    $fullUrl   = $protocol . '://' . $host . $basePath . $book['b_pdf_url'];

    echo json_encode([
        'success'   => true,
        'pdf_url'   => $fullUrl,
        'book_title' => $book['b_title']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}