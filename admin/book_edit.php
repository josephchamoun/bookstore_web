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
        .success { background: #1a3a1a; border: 1px solid #4caf50; color: #4caf50; padding: 12px 16px; border-radius: 8px; margin-top: 16px; display: none; }
        .error   { background: #3a1a1a; border: 1px solid #f44336; color: #f44336; padding: 12px 16px; border-radius: 8px; margin-top: 16px; display: none; }
        .preview { width: 80px; height: 110px; object-fit: cover; border-radius: 6px; margin-top: 8px; background: #242424; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <a href="books.php" class="back">← Back to Books</a>
    <div class="page-title">Edit Book</div>

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

        <label>COVER IMAGE URL</label>
        <input type="text" id="coverUrl" oninput="previewCover()" />
        <img id="preview" class="preview" />

        <div class="success" id="success">✓ Book updated successfully!</div>
        <div class="error"   id="error">Failed to update book.</div>

        <button class="btn btn-primary" onclick="updateBook()">Save Changes</button>
    </div>
</div>

<script>
const bookId = new URLSearchParams(window.location.search).get('id');
const adminToken = localStorage.getItem('admin_token');
if (!adminToken) window.location.href = '/bookstore_api/admin/login.php';

async function apiFetch(url, options = {}) {
    return fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
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

    document.getElementById('bookId').value   = book.book_id;
    document.getElementById('title').value    = book.b_title;
    document.getElementById('author').value   = book.b_author;
    document.getElementById('price').value    = book.b_price;
    document.getElementById('stock').value    = book.b_stock;
    document.getElementById('coverUrl').value = book.b_cover_url;
    document.getElementById('preview').src    = book.b_cover_url;

    const sel = document.getElementById('category');
    (catsData.categories || []).forEach(c => {
        sel.innerHTML += `<option value="${c.category_id}" ${c.category_id == book.category_id ? 'selected' : ''}>${c.c_name}</option>`;
    });
}

function previewCover() {
    document.getElementById('preview').src = document.getElementById('coverUrl').value;
}

async function updateBook() {
    document.getElementById('success').style.display = 'none';
    document.getElementById('error').style.display   = 'none';

    const res = await apiFetch('/bookstore_api/api/admin/admin_books.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            book_id:     document.getElementById('bookId').value,
            b_title:     document.getElementById('title').value,
            b_author:    document.getElementById('author').value,
            category_id: document.getElementById('category').value,
            b_price:     document.getElementById('price').value,
            b_stock:     document.getElementById('stock').value,
            b_cover_url: document.getElementById('coverUrl').value
        })
    });

    if (res.ok) document.getElementById('success').style.display = 'block';
    else        document.getElementById('error').style.display   = 'block';
}

loadData();
</script>
</body>
</html>