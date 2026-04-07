<?php require_once __DIR__ . '/../config/admin_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Books — BookStore Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0d0d0d; color: #fff; display: flex; min-height: 100vh; }
        .sidebar { width: 240px; background: #1a1a1a; border-right: 1px solid #2a2a2a; padding: 24px 0; position: fixed; height: 100vh; display: flex; flex-direction: column; }
        .sidebar-logo { padding: 0 24px 24px; border-bottom: 1px solid #2a2a2a; font-size: 18px; font-weight: 700; }
        .sidebar-logo span { color: #2d6ef5; }
        .nav { margin-top: 16px; flex: 1; }
        .nav a { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: #9e9e9e; text-decoration: none; font-size: 14px; transition: all 0.2s; }
        .nav a:hover, .nav a.active { background: #242424; color: #fff; border-left: 3px solid #2d6ef5; }
        .nav a .icon { font-size: 18px; width: 24px; }
        .logout { padding: 16px 24px; border-top: 1px solid #2a2a2a; }
        .logout a { color: #f44336; text-decoration: none; font-size: 14px; }
        .main { margin-left: 240px; flex: 1; padding: 32px; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .page-title { font-size: 26px; font-weight: 700; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; }
        .btn-primary { background: #2d6ef5; color: #fff; }
        .btn-primary:hover { background: #1a4fbf; }
        .btn-danger  { background: #3a1a1a; color: #f44336; border: 1px solid #f44336; }
        .btn-edit    { background: #1a2a3a; color: #2d6ef5; border: 1px solid #2d6ef5; }
        table { width: 100%; border-collapse: collapse; background: #1a1a1a; border-radius: 12px; overflow: hidden; }
        th { background: #242424; padding: 14px 16px; text-align: left; font-size: 11px; color: #9e9e9e; letter-spacing: 1px; }
        td { padding: 14px 16px; border-bottom: 1px solid #2a2a2a; font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #1f1f1f; }
        .cover { width: 40px; height: 55px; object-fit: cover; border-radius: 4px; background: #242424; }
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; background: #242424; color: #9e9e9e; }
        .search { padding: 10px 16px; background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 8px; color: #fff; font-size: 14px; width: 260px; outline: none; }
        .search:focus { border-color: #2d6ef5; }
        .actions { display: flex; gap: 8px; }
        .toast { position: fixed; bottom: 24px; right: 24px; background: #1a3a1a; border: 1px solid #4caf50; color: #4caf50; padding: 12px 20px; border-radius: 8px; font-size: 14px; display: none; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="header">
        <div class="page-title">📚 Books</div>
        <div style="display:flex;gap:12px;align-items:center">
            <input type="text" class="search" id="search" placeholder="Search books..." oninput="filterBooks()" />
            <a href="book_add.php" class="btn btn-primary">+ Add Book</a>
        </div>
    </div>

    <table id="booksTable">
        <thead>
            <tr>
                <th>COVER</th>
                <th>TITLE</th>
                <th>AUTHOR</th>
                <th>CATEGORY</th>
                <th>PRICE</th>
                <th>STOCK</th>
                <th>ACTIONS</th>
            </tr>
        </thead>
        <tbody id="booksBody">
            <tr><td colspan="7" style="text-align:center;color:#9e9e9e;padding:40px">Loading...</td></tr>
        </tbody>
    </table>
</div>

<div class="toast" id="toast">✓ Book deleted successfully</div>

<script>
let allBooks = [];

async function loadBooks() {
    const res  = await fetch('/bookstore_api/api/admin/admin_books.php');
    const data = await res.json();
    allBooks   = data.books || [];
    renderBooks(allBooks);
}

function renderBooks(books) {
    const tbody = document.getElementById('booksBody');
    if (books.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#9e9e9e;padding:40px">No books found</td></tr>';
        return;
    }
    tbody.innerHTML = books.map(b => `
        <tr>
            <td><img src="${b.b_cover_url || ''}" class="cover" onerror="this.style.display='none'" /></td>
            <td><strong>${b.b_title}</strong></td>
            <td style="color:#9e9e9e">${b.b_author}</td>
            <td><span class="badge">${b.c_name}</span></td>
            <td style="color:#2d6ef5">$${parseFloat(b.b_price).toFixed(2)}</td>
            <td>${b.b_stock}</td>
            <td>
                <div class="actions">
                    <a href="book_edit.php?id=${b.book_id}" class="btn btn-edit">Edit</a>
                    <button class="btn btn-danger" onclick="deleteBook(${b.book_id})">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function filterBooks() {
    const q = document.getElementById('search').value.toLowerCase();
    renderBooks(allBooks.filter(b =>
        b.b_title.toLowerCase().includes(q) ||
        b.b_author.toLowerCase().includes(q)
    ));
}

async function deleteBook(id) {
    if (!confirm('Are you sure you want to delete this book?')) return;
    await fetch('/bookstore_api/api/admin/admin_books.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ book_id: id })
    });
    showToast();
    loadBooks();
}

function showToast() {
    const t = document.getElementById('toast');
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3000);
}

loadBooks();
</script>
</body>
</html>