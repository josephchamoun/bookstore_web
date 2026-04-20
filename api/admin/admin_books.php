<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/admin_auth.php';

requireAdminAuth();

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── PDF upload helper ─────────────────────────────────────────────────────────
function handlePdfUpload(): ?string {
    if (!isset($_FILES['b_pdf']) || $_FILES['b_pdf']['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // no file uploaded — not an error
    }

    $file = $_FILES['b_pdf'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'PDF upload failed with code: ' . $file['error']]);
        exit;
    }

    // Validate it's actually a PDF
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mimeType !== 'application/pdf') {
        http_response_code(400);
        echo json_encode(['error' => 'Only PDF files are allowed']);
        exit;
    }

    // Max 50MB
    if ($file['size'] > 50 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'PDF must be under 50MB']);
        exit;
    }

    // Save to uploads/pdfs/
    $uploadDir = __DIR__ . '/../../uploads/pdfs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = uniqid('book_', true) . '.pdf';
    $destPath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save PDF file']);
        exit;
    }

    // Return relative URL Android will use
    return 'uploads/pdfs/' . $fileName;
}

// ── Delete old PDF file from disk ─────────────────────────────────────────────
function deleteOldPdf(string $pdfUrl): void {
    if (empty($pdfUrl)) return;
    $fullPath = __DIR__ . '/../../' . $pdfUrl;
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

// Determine if this is a create or update
// PUT can't receive multipart, so edit_book.php sends POST with ?action=update
$isUpdate = ($method === 'PUT') || 
            ($method === 'POST' && ($_GET['action'] ?? '') === 'update');

switch (true) {

    // ── GET all books ─────────────────────────────────────────────────────────
    case $method === 'GET':
        $stmt = $pdo->query('
            SELECT b.*, c.c_name,
                   CASE WHEN b.b_pdf_url IS NOT NULL AND b.b_pdf_url != \'\'
                        THEN 1 ELSE 0 END AS has_ebook
            FROM books b
            JOIN categories c ON b.category_id = c.category_id
            ORDER BY b.b_title
        ');
        echo json_encode(['books' => $stmt->fetchAll()]);
        break;

    // ── POST add new book ─────────────────────────────────────────────────────
    case $method === 'POST' && !$isUpdate:
        $categoryId = $_POST['category_id']  ?? null;
        $title      = $_POST['b_title']      ?? '';
        $author     = $_POST['b_author']     ?? '';
        $price      = $_POST['b_price']      ?? 0;
        $stock      = $_POST['b_stock']      ?? 0;
        $coverUrl   = $_POST['b_cover_url']  ?? '';

        if (!$categoryId || !$title || !$author) {
            http_response_code(400);
            echo json_encode(['error' => 'category_id, b_title and b_author are required']);
            exit;
        }

        $pdfUrl = handlePdfUpload();

        $stmt = $pdo->prepare('
            INSERT INTO books (category_id, b_title, b_author, b_price, b_stock, b_cover_url, b_pdf_url)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$categoryId, $title, $author, $price, $stock, $coverUrl, $pdfUrl]);

        http_response_code(201);
        echo json_encode([
            'message'   => 'Book added',
            'book_id'   => $pdo->lastInsertId(),
            'has_ebook' => $pdfUrl !== null
        ]);
        break;

    // ── PUT/POST?action=update — edit existing book ───────────────────────────
    case $isUpdate:
        $bookId     = $_POST['book_id']      ?? null;
        $categoryId = $_POST['category_id']  ?? null;
        $title      = $_POST['b_title']      ?? '';
        $author     = $_POST['b_author']     ?? '';
        $price      = $_POST['b_price']      ?? 0;
        $stock      = $_POST['b_stock']      ?? 0;
        $coverUrl   = $_POST['b_cover_url']  ?? '';
        $removePdf  = ($_POST['remove_pdf']  ?? '0') === '1';

        if (!$bookId || !$categoryId || !$title || !$author) {
            http_response_code(400);
            echo json_encode(['error' => 'book_id, category_id, b_title and b_author are required']);
            exit;
        }

        // Get existing PDF URL
        $existing      = $pdo->prepare('SELECT b_pdf_url FROM books WHERE book_id = ?');
        $existing->execute([$bookId]);
        $currentPdfUrl = $existing->fetchColumn() ?: '';

        $newPdfUrl = $currentPdfUrl; // default: keep existing

        if ($removePdf) {
            deleteOldPdf($currentPdfUrl);
            $newPdfUrl = null;
        } else {
            $uploaded = handlePdfUpload();
            if ($uploaded !== null) {
                deleteOldPdf($currentPdfUrl);
                $newPdfUrl = $uploaded;
            }
        }

        $stmt = $pdo->prepare('
            UPDATE books
            SET category_id=?, b_title=?, b_author=?, b_price=?, b_stock=?, b_cover_url=?, b_pdf_url=?
            WHERE book_id=?
        ');
        $stmt->execute([
            $categoryId, $title, $author, $price, $stock, $coverUrl, $newPdfUrl, $bookId
        ]);

        echo json_encode([
            'message'   => 'Book updated',
            'has_ebook' => !empty($newPdfUrl)
        ]);
        break;

    // ── DELETE book ───────────────────────────────────────────────────────────
    case $method === 'DELETE':
        $data   = json_decode(file_get_contents('php://input'), true);
        $bookId = $data['book_id'] ?? null;

        if (!$bookId) {
            http_response_code(400);
            echo json_encode(['error' => 'book_id is required']);
            exit;
        }

        $pdfStmt = $pdo->prepare('SELECT b_pdf_url FROM books WHERE book_id = ?');
        $pdfStmt->execute([$bookId]);
        $pdfUrl = $pdfStmt->fetchColumn();
        if ($pdfUrl) deleteOldPdf($pdfUrl);

        $stmt = $pdo->prepare('DELETE FROM books WHERE book_id = ?');
        $stmt->execute([$bookId]);
        echo json_encode(['message' => 'Book deleted']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}