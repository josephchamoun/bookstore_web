<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/admin_auth.php';

requireAdminAuth();

$method   = $_SERVER['REQUEST_METHOD'];
$isUpdate = ($method === 'PUT') ||
            ($method === 'POST' && ($_GET['action'] ?? '') === 'update');

// ── Upload cover to Firebase Storage REST API ─────────────────────────────────
function handleCoverUpload(string $bookId): ?string {
    if (!isset($_FILES['b_cover']) || $_FILES['b_cover']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES['b_cover'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Cover upload failed']);
        exit;
    }
    return uploadToStorage($file['tmp_name'], 'covers/' . $bookId . '_' . uniqid() . '.jpg', $file['type'], true);
}

// ── Upload PDF to Firebase Storage REST API ───────────────────────────────────
function handlePdfUpload(string $bookId): ?string {
    if (!isset($_FILES['b_pdf']) || $_FILES['b_pdf']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES['b_pdf'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'PDF upload failed']);
        exit;
    }
    if ($file['size'] > 50 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'PDF must be under 50MB']);
        exit;
    }
    return uploadToStorage($file['tmp_name'], 'ebooks/' . $bookId . '_' . uniqid() . '.pdf', 'application/pdf', false);
}

// ── Upload file to Firebase Storage via REST ──────────────────────────────────
function uploadToStorage(string $filePath, string $storagePath, string $mimeType, bool $public): string {
    $bucket      = FIREBASE_PROJECT_ID . '.appspot.com';
    $token       = getAccessToken();
    $encodedPath = urlencode($storagePath);
    $url         = "https://storage.googleapis.com/upload/storage/v1/b/{$bucket}/o?uploadType=media&name={$encodedPath}";
    if ($public) $url .= '&predefinedAcl=publicRead';

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Authorization: Bearer $token\r\nContent-Type: $mimeType\r\n",
            'content' => file_get_contents($filePath),
            'ignore_errors' => true,
        ]
    ];

    $context = stream_context_create($options);
    file_get_contents($url, false, $context);

    if ($public) {
        return "https://storage.googleapis.com/{$bucket}/{$storagePath}";
    }
    return "gs://{$bucket}/{$storagePath}";
}

switch (true) {

    // ── GET all books ─────────────────────────────────────────────────────────
    case $method === 'GET':
        $books = fsGetCollection('books');
        foreach ($books as &$b) $b['book_id'] = $b['id'];
        usort($books, fn($a, $b) => strcmp($a['title'] ?? '', $b['title'] ?? ''));
        echo json_encode(['books' => $books]);
        break;

    // ── POST add new book ─────────────────────────────────────────────────────
    case $method === 'POST' && !$isUpdate:
        $categoryId   = $_POST['category_id']  ?? '';
        $categoryName = $_POST['category_name'] ?? '';
        $title        = $_POST['b_title']       ?? '';
        $author       = $_POST['b_author']      ?? '';
        $price        = (float)($_POST['b_price'] ?? 0);
        $stock        = (int)($_POST['b_stock']   ?? 0);
        $coverUrl     = $_POST['b_cover_url']   ?? '';

        if (!$categoryId || !$title || !$author) {
            http_response_code(400);
            echo json_encode(['error' => 'category_id, b_title and b_author are required']);
            exit;
        }

        // Generate a temp ID for file naming
        $bookId = uniqid('book_', true);

        $uploadedCover = handleCoverUpload($bookId);
        $uploadedPdf   = handlePdfUpload($bookId);

        $finalCoverUrl = $uploadedCover ?? $coverUrl;
        $hasEbook      = $uploadedPdf !== null;
        $ebookUrl      = $uploadedPdf ?? '';

        // ← CHANGED: fsAddDocument instead of $ref->set()
        $newId = fsAddDocument('books', [
            'title'        => $title,
            'author'       => $author,
            'price'        => $price,
            'stock'        => $stock,
            'coverUrl'     => $finalCoverUrl,
            'categoryId'   => $categoryId,
            'categoryName' => $categoryName,
            'hasEbook'     => $hasEbook,
            'ebookUrl'     => $ebookUrl,
            'favorites'    => [],
        ]);

        http_response_code(201);
        echo json_encode([
            'message'   => 'Book added',
            'book_id'   => $newId,
            'has_ebook' => $hasEbook
        ]);
        break;

    // ── POST?action=update — edit existing book ───────────────────────────────
    case $isUpdate:
        $bookId       = $_POST['book_id']       ?? '';
        $categoryId   = $_POST['category_id']   ?? '';
        $categoryName = $_POST['category_name'] ?? '';
        $title        = $_POST['b_title']       ?? '';
        $author       = $_POST['b_author']      ?? '';
        $price        = (float)($_POST['b_price'] ?? 0);
        $stock        = (int)($_POST['b_stock']   ?? 0);
        $coverUrl     = $_POST['b_cover_url']   ?? '';
        $removePdf    = ($_POST['remove_pdf']   ?? '0') === '1';

        if (!$bookId || !$categoryId || !$title || !$author) {
            http_response_code(400);
            echo json_encode(['error' => 'book_id, category_id, b_title and b_author are required']);
            exit;
        }

        $uploadedCover = handleCoverUpload($bookId);
        $uploadedPdf   = handlePdfUpload($bookId);

        // Build update data
        $updates = [
            'title'        => $title,
            'author'       => $author,
            'price'        => $price,
            'stock'        => $stock,
            'categoryId'   => $categoryId,
            'categoryName' => $categoryName,
        ];

        if ($uploadedCover) {
            $updates['coverUrl'] = $uploadedCover;
        } elseif ($coverUrl) {
            $updates['coverUrl'] = $coverUrl;
        }

        if ($removePdf) {
            $updates['hasEbook'] = false;
            $updates['ebookUrl'] = '';
        } elseif ($uploadedPdf) {
            $updates['hasEbook'] = true;
            $updates['ebookUrl'] = $uploadedPdf;
        }

        // ← CHANGED: fsUpdateDocument instead of ->update()
        fsUpdateDocument('books', $bookId, $updates);
        echo json_encode(['message' => 'Book updated']);
        break;

    // ── DELETE book ───────────────────────────────────────────────────────────
    case $method === 'DELETE':
        $data   = json_decode(file_get_contents('php://input'), true);
        $bookId = $data['book_id'] ?? '';

        if (!$bookId) {
            http_response_code(400);
            echo json_encode(['error' => 'book_id is required']);
            exit;
        }

        // ← CHANGED: fsDeleteDocument instead of ->delete()
        fsDeleteDocument('books', $bookId);
        echo json_encode(['message' => 'Book deleted']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}