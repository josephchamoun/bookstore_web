<?php require_once __DIR__ . '/../config/admin_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Book — BookStore Admin</title>
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
        .preview { width: 80px; height: 110px; object-fit: cover; border-radius: 6px; margin-top: 8px; background: #242424; display: none; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <a href="books.php" class="back">← Back to Books</a>
    <div class="page-title">Add New Book</div>

    <div class="card" style="margin-top:24px">
        <label>BOOK TITLE</label>
        <input type="text" id="title" placeholder="e.g. The Great Gatsby" />

        <label>AUTHOR</label>
        <input type="text" id="author" placeholder="e.g. F. Scott Fitzgerald" />

        <label>CATEGORY</label>
        <select id="category">
            <option value="">Select category...</option>
        </select>

        <div class="row">
            <div>
                <label>PRICE ($)</label>
                <input type="number" id="price" placeholder="0.00" step="0.01" min="0" />
            </div>
            <div>
                <label>STOCK</label>
                <input type="number" id="stock" placeholder="0" min="0" />
            </div>
        </div>

        <label>COVER IMAGE</label>
        <div style="display:flex;align-items:center;gap:16px;margin-top:6px">
            <img id="preview" class="preview" style="display:none;width:80px;height:110px;object-fit:cover;border-radius:6px" />
            <div>
                <input type="file" id="coverFile" accept="image/*" onchange="uploadCover()" style="display:none" />
                <button type="button" class="btn" onclick="document.getElementById('coverFile').click()"
                    style="background:#242424;color:#fff;border:1px solid #2a2a2a">
                    📁 Choose Image
                </button>
                <div id="uploadStatus" style="font-size:12px;color:#9e9e9e;margin-top:6px">No image selected</div>
            </div>
        </div>
        <input type="hidden" id="coverUrl" />

        <div class="success" id="success">✓ Book added successfully!</div>
        <div class="error"   id="error">Failed to add book. Please check all fields.</div>

        <button class="btn btn-primary" onclick="addBook()">Add Book</button>
    </div>
</div>

<script>
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

async function loadCategories() {
    const res  = await apiFetch('/bookstore_api/api/admin/admin_categories.php');
    const data = await res.json();
    const sel  = document.getElementById('category');
    (data.categories || []).forEach(c => {
        sel.innerHTML += `<option value="${c.category_id}">${c.c_name}</option>`;
    });
}

async function uploadCover() {
    const file = document.getElementById('coverFile').files[0];
    if (!file) return;

    document.getElementById('uploadStatus').textContent = 'Uploading...';

    const formData = new FormData();
    formData.append('cover', file);

    const res = await fetch('/bookstore_api/api/admin/upload_cover.php', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + adminToken },
        body: formData
    });

    const data = await res.json();

    if (res.ok) {
        document.getElementById('coverUrl').value      = data.url;
        document.getElementById('preview').src         = data.url;
        document.getElementById('preview').style.display = 'block';
        document.getElementById('uploadStatus').textContent = '✓ Uploaded';
        document.getElementById('uploadStatus').style.color = '#4caf50';
    } else {
        document.getElementById('uploadStatus').textContent = data.error || 'Upload failed';
        document.getElementById('uploadStatus').style.color = '#f44336';
    }
}

async function addBook() {
    const title    = document.getElementById('title').value.trim();
    const author   = document.getElementById('author').value.trim();
    const category = document.getElementById('category').value;
    const price    = document.getElementById('price').value;
    const stock    = document.getElementById('stock').value;
    const coverUrl = document.getElementById('coverUrl').value.trim();

    document.getElementById('success').style.display = 'none';
    document.getElementById('error').style.display   = 'none';

    if (!title || !author || !category || !price || !stock) {
        document.getElementById('error').style.display = 'block';
        document.getElementById('error').textContent   = 'Please fill all required fields.';
        return;
    }

    const res = await apiFetch('/bookstore_api/api/admin/admin_books.php', {
        method: 'POST',
        body: JSON.stringify({
            b_title: title, b_author: author,
            category_id: category, b_price: price,
            b_stock: stock, b_cover_url: coverUrl
        })
    });

    if (res.ok) {
        document.getElementById('success').style.display = 'block';
        document.getElementById('title').value    = '';
        document.getElementById('author').value   = '';
        document.getElementById('price').value    = '';
        document.getElementById('stock').value    = '';
        document.getElementById('coverUrl').value = '';
        document.getElementById('preview').style.display = 'none';
    } else {
        document.getElementById('error').style.display = 'block';
    }
}

loadCategories();
</script>
</body>
</html>