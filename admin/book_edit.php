<?php require_once __DIR__ . '/../config/admin_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Book — BookStore Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0d0d0d; color: #fff; display: flex; min-height: 100vh; }
        .sidebar { width: 240px; background: #1a1a1a; border-right: 1px solid #2a2a2a; padding: 24px 0; position: fixed; height: 100vh; display: flex; flex-direction: column; }
        .sidebar-logo { padding: 0 24px 24px; border-bottom: 1px solid #2a2a2a; font-size: 18px; font-weight: 700; }
        .sidebar-logo span { color: #2d6ef5; }
        .nav { margin-top: 16px; flex: 1; }
        .nav a { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: #9e9e9e; text-decoration: none; font-size: 14px; }
        .nav a:hover, .nav a.active { background: #242424; color: #fff; border-left: 3px solid #2d6ef5; }
        .nav a .icon { font-size: 18px; width: 24px; }
        .logout { padding: 16px 24px; border-top: 1px solid #2a2a2a; }
        .logout a { color: #f44336; text-decoration: none; font-size: 14px; }
        .main { margin-left: 240px; flex: 1; padding: 32px; max-width: 700px; }
        .page-title { font-size: 26px; font-weight: 700; margin-bottom: 8px; }
        .back { color: #9e9e9e; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 24px; }
        .back:hover { color: #fff; }
        .card { background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 12px; padding: 28px; }
        label { display: block; font-size: 11px; color: #9e9e9e; letter-spacing: 1px; margin-bottom: 6px; margin-top: 20px; }
        label:first-child { margin-top: 0; }
        input, select { width: 100%; padding: 12px 14px; background: #242424; border: 1px solid #2a2a2a; border-radius: 8px; color: #fff; font-size: 14px; outline: none; }
        input:focus, select:focus { border-color: #2d6ef5; }
        select option { background: #242424; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn { padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; }
        .btn-primary { background: #2d6ef5; color: #fff; width: 100%; margin-top: 24px; }
        .btn-primary:hover { background: #1a4fbf; }
        .btn-secondary { background: #242424; color: #fff; border: 1px solid #2a2a2a; }
        .btn-secondary:hover { background: #2a2a2a; }
        .btn-danger { background: #3a1a1a; color: #f44336; border: 1px solid #f44336; }
        .btn-danger:hover { background: #4a2a2a; }
        .success { background: #1a3a1a; border: 1px solid #4caf50; color: #4caf50; padding: 12px 16px; border-radius: 8px; margin-top: 16px; display: none; }
        .error   { background: #3a1a1a; border: 1px solid #f44336; color: #f44336; padding: 12px 16px; border-radius: 8px; margin-top: 16px; display: none; }
        .divider { border: none; border-top: 1px solid #2a2a2a; margin: 24px 0; }
        .section-label { font-size: 12px; color: #666; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 16px; }
        .pdf-box { background: #242424; border: 1px dashed #2a2a2a; border-radius: 8px; padding: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 8px; }
        .pdf-info { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
        .pdf-icon { font-size: 22px; flex-shrink: 0; }
        .pdf-name { font-size: 13px; color: #9e9e9e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pdf-name.ready { color: #4caf50; }
        .pdf-name.existing { color: #2d6ef5; }
        .upload-status { font-size: 12px; margin-top: 6px; color: #9e9e9e; }
        .ebook-badge { display: inline-block; background: #1a2a3a; color: #2d6ef5; border: 1px solid #2d6ef5; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; margin-left: 10px; vertical-align: middle; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <a href="books.php" class="back">← Back to Books</a>
    <div class="page-title" id="pageTitle">Edit Book</div>

    <div class="card" style="margin-top:24px">
        <input type="hidden" id="bookId" />

        <label>BOOK TITLE</label>
        <input type="text" id="title" />

        <label>AUTHOR</label>
        <input type="text" id="author" />

        <label>CATEGORY</label>
        <select id="category"></select>

        <div class="row">
            <div>
                <label>PRICE ($)</label>
                <input type="number" id="price" step="0.01" min="0" />
            </div>
            <div>
                <label>STOCK</label>
                <input type="number" id="stock" min="0" />
            </div>
        </div>

        <label>COVER IMAGE</label>
        <div style="display:flex;align-items:center;gap:16px;margin-top:6px">
            <img id="preview" style="display:none;width:80px;height:110px;object-fit:cover;border-radius:6px" />
            <div>
                <input type="file" id="coverFile" accept="image/*" onchange="uploadCover()" style="display:none" />
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('coverFile').click()">
                    📁 Choose Image
                </button>
                <div id="coverStatus" class="upload-status">No new image selected</div>
            </div>
        </div>
        <input type="hidden" id="coverUrl" />

        <!-- ── PDF / Ebook section ── -->
        <hr class="divider" />
        <div class="section-label">📖 Ebook (optional)</div>

        <!-- Current PDF status banner -->
        <div id="currentPdfBanner" style="display:none;background:#1a2a3a;border:1px solid #2d6ef5;border-radius:8px;padding:12px 16px;margin-bottom:12px;font-size:13px;color:#2d6ef5;display:flex;align-items:center;justify-content:space-between">
            <span>📄 This book has an ebook attached</span>
            <button class="btn btn-danger" onclick="markRemovePdf()" style="padding:6px 14px;font-size:12px">Remove PDF</button>
        </div>

        <div class="pdf-box" id="pdfBox">
            <div class="pdf-info">
                <span class="pdf-icon">📄</span>
                <span class="pdf-name" id="pdfName">No new PDF selected</span>
            </div>
            <div style="display:flex;gap:8px;flex-shrink:0">
                <input type="file" id="pdfFile" accept="application/pdf" onchange="previewPdf()" style="display:none" />
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('pdfFile').click()">
                    Upload new PDF
                </button>
                <button type="button" class="btn btn-danger" id="btnClearNewPdf" onclick="clearNewPdf()" style="display:none">
                    Cancel
                </button>
            </div>
        </div>
        <div class="upload-status" id="pdfStatus">Choose a PDF to add or replace the ebook.</div>

        <div class="success" id="success">✓ Book updated successfully!</div>
        <div class="error"   id="error">Failed to update book.</div>

        <button class="btn btn-primary" onclick="updateBook()">Save Changes</button>
    </div>
</div>

<script>
const bookId     = new URLSearchParams(window.location.search).get('id');
const adminToken = localStorage.getItem('admin_token');
if (!adminToken) window.location.href = '/bookstore_api/admin/login.php';

let removePdfFlag = false; // tracks if admin clicked "Remove PDF"

async function apiFetch(url, options = {}) {
    return fetch(url, {
        ...options,
        headers: {
            'Authorization': 'Bearer ' + adminToken,
            ...(options.headers || {})
        }
    });
}

async function loadData() {
    const [booksRes, catsRes] = await Promise.all([
        apiFetch('/bookstore_api/api/admin/admin_books.php'),
        apiFetch('/bookstore_api/api/admin/admin_categories.php')
    ]);
    const booksData = await booksRes.json();
    const catsData  = await catsRes.json();

    const book = booksData.books?.find(b => b.book_id == bookId);
    if (!book) { alert('Book not found'); return; }

    document.getElementById('bookId').value  = book.book_id;
    document.getElementById('title').value   = book.b_title;
    document.getElementById('author').value  = book.b_author;
    document.getElementById('price').value   = book.b_price;
    document.getElementById('stock').value   = book.b_stock;

    // Page title
    document.getElementById('pageTitle').textContent = 'Edit Book';
    if (book.has_ebook == 1) {
        document.getElementById('pageTitle').innerHTML +=
            '<span class="ebook-badge">Has Ebook</span>';
    }

    // Cover
    if (book.b_cover_url) {
        document.getElementById('coverUrl').value         = book.b_cover_url;
        document.getElementById('preview').src            = book.b_cover_url;
        document.getElementById('preview').style.display  = 'block';
        document.getElementById('coverStatus').textContent = '✓ Current cover loaded';
    }

    // Show current PDF banner if book already has one
    if (book.has_ebook == 1) {
        document.getElementById('currentPdfBanner').style.display = 'flex';
        document.getElementById('pdfStatus').textContent =
            'Book already has an ebook. Upload a new PDF to replace it.';
    }

    // Categories
    const sel = document.getElementById('category');
    (catsData.categories || []).forEach(c => {
        sel.innerHTML += `<option value="${c.category_id}"
            ${c.category_id == book.category_id ? 'selected' : ''}>
            ${c.c_name}</option>`;
    });
}

async function uploadCover() {
    const file = document.getElementById('coverFile').files[0];
    if (!file) return;

    document.getElementById('coverStatus').textContent = 'Uploading...';

    const formData = new FormData();
    formData.append('cover', file);

    const res  = await apiFetch('/bookstore_api/api/admin/upload_cover.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();

    if (res.ok) {
        document.getElementById('coverUrl').value           = data.url;
        document.getElementById('preview').src             = data.url;
        document.getElementById('preview').style.display   = 'block';
        document.getElementById('coverStatus').textContent = '✓ Uploaded';
        document.getElementById('coverStatus').style.color = '#4caf50';
    } else {
        document.getElementById('coverStatus').textContent = data.error || 'Upload failed';
        document.getElementById('coverStatus').style.color = '#f44336';
    }
}

// ── PDF helpers ───────────────────────────────────────────────────────────────
function previewPdf() {
    const file = document.getElementById('pdfFile').files[0];
    if (!file) return;
    removePdfFlag = false; // uploading new — cancel any remove flag
    document.getElementById('pdfName').textContent         = file.name;
    document.getElementById('pdfName').className           = 'pdf-name ready';
    document.getElementById('btnClearNewPdf').style.display = 'inline-block';
    document.getElementById('pdfBox').style.borderColor    = '#4caf50';
    document.getElementById('pdfStatus').textContent       =
        `✓ ${(file.size / 1024 / 1024).toFixed(1)} MB — will replace existing PDF on save`;
    document.getElementById('pdfStatus').style.color       = '#4caf50';
}

function clearNewPdf() {
    document.getElementById('pdfFile').value               = '';
    document.getElementById('pdfName').textContent         = 'No new PDF selected';
    document.getElementById('pdfName').className           = 'pdf-name';
    document.getElementById('btnClearNewPdf').style.display = 'none';
    document.getElementById('pdfBox').style.borderColor    = '#2a2a2a';
    document.getElementById('pdfStatus').textContent       =
        'Choose a PDF to add or replace the ebook.';
    document.getElementById('pdfStatus').style.color       = '#9e9e9e';
}

function markRemovePdf() {
    removePdfFlag = true;
    clearNewPdf();
    document.getElementById('currentPdfBanner').style.background = '#3a1a1a';
    document.getElementById('currentPdfBanner').style.borderColor = '#f44336';
    document.getElementById('currentPdfBanner').style.color = '#f44336';
    document.getElementById('currentPdfBanner').querySelector('span').textContent =
        '⚠ PDF will be removed when you save';
    document.getElementById('currentPdfBanner').querySelector('button').textContent = 'Undo';
    document.getElementById('currentPdfBanner').querySelector('button').onclick = undoRemovePdf;
}

function undoRemovePdf() {
    removePdfFlag = false;
    document.getElementById('currentPdfBanner').style.background  = '#1a2a3a';
    document.getElementById('currentPdfBanner').style.borderColor = '#2d6ef5';
    document.getElementById('currentPdfBanner').style.color       = '#2d6ef5';
    document.getElementById('currentPdfBanner').querySelector('span').textContent =
        '📄 This book has an ebook attached';
    document.getElementById('currentPdfBanner').querySelector('button').textContent = 'Remove PDF';
    document.getElementById('currentPdfBanner').querySelector('button').onclick = markRemovePdf;
}

async function updateBook() {
    document.getElementById('success').style.display = 'none';
    document.getElementById('error').style.display   = 'none';

    const pdfFile = document.getElementById('pdfFile').files[0];

    // ── Send as FormData so PHP can receive the PDF file ─────────────────────
    const formData = new FormData();
    formData.append('book_id',      document.getElementById('bookId').value);
    formData.append('category_id',  document.getElementById('category').value);
    formData.append('b_title',      document.getElementById('title').value);
    formData.append('b_author',     document.getElementById('author').value);
    formData.append('b_price',      document.getElementById('price').value);
    formData.append('b_stock',      document.getElementById('stock').value);
    formData.append('b_cover_url',  document.getElementById('coverUrl').value);
    formData.append('remove_pdf',   removePdfFlag ? '1' : '0');
    if (pdfFile) formData.append('b_pdf', pdfFile);

    const res = await apiFetch('/bookstore_api/api/admin/admin_books.php?action=update', {
        method: 'POST',
        body: formData
    });

    if (res.ok) {
        document.getElementById('success').style.display = 'block';
        // Reload to reflect new ebook badge status
        setTimeout(() => loadData(), 1000);
    } else {
        const data = await res.json().catch(() => ({}));
        document.getElementById('error').style.display  = 'block';
        document.getElementById('error').textContent    = data.error || 'Failed to update book.';
    }
}

loadData();
</script>
</body>
</html>