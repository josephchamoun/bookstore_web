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

$pdo = getDB();

$sql    = 'SELECT b.*, c.c_name,
                  CASE WHEN b.b_pdf_url IS NOT NULL AND b.b_pdf_url != \'\'
                       THEN 1 ELSE 0 END AS has_ebook
           FROM books b
           JOIN categories c ON b.category_id = c.category_id
           WHERE 1=1';
$params = [];

// Optional filter by category
if (!empty($_GET['category_id'])) {
    $sql     .= ' AND b.category_id = ?';
    $params[] = (int) $_GET['category_id'];
}

// Optional search by title or author
if (!empty($_GET['search'])) {
    $sql     .= ' AND (b.b_title LIKE ? OR b.b_author LIKE ?)';
    $params[] = '%' . $_GET['search'] . '%';
    $params[] = '%' . $_GET['search'] . '%';
}

// Only return books changed after this timestamp
if (!empty($_GET['since'])) {
    $sql     .= ' AND b.updated_at > ?';
    $params[] = $_GET['since'];
}

$sql .= ' ORDER BY b.b_title';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get MAX updated_at from the FULL table (not just filtered results)
// so Android always knows the true latest timestamp
$maxStmt = $pdo->query('SELECT MAX(updated_at) as last_updated FROM books');
$maxUpdated = $maxStmt->fetch()['last_updated'];

echo json_encode([
    'books'        => $books,
    'last_updated' => $maxUpdated
]);