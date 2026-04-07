<?php require_once __DIR__ . '/../config/admin_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories — BookStore Admin</title>
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
        .main { margin-left: 240px; flex: 1; padding: 32px; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .page-title { font-size: 26px; font-weight: 700; }
        .content { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .card { background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 12px; padding: 24px; }
        .card h2 { font-size: 16px; margin-bottom: 20px; }
        label { display: block; font-size: 11px; color: #9e9e9e; letter-spacing: 1px; margin-bottom: 6px; }
        input { width: 100%; padding: 12px 14px; background: #242424; border: 1px solid #2a2a2a; border-radius: 8px; color: #fff; font-size: 14px; outline: none; }
        input:focus { border-color: #2d6ef5; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; }
        .btn-primary { background: #2d6ef5; color: #fff; width: 100%; margin-top: 16px; }
        .btn-danger  { background: transparent; color: #f44336; border: 1px solid #f44336; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; }
        .btn-edit    { background: transparent; color: #2d6ef5; border: 1px solid #2d6ef5; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; }
        .cat-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #2a2a2a; }
        .cat-item:last-child { border-bottom: none; }
        .cat-name { font-size: 14px; }
        .cat-actions { display: flex; gap: 8px; }
        .success { background: #1a3a1a; border: 1px solid #4caf50; color: #4caf50; padding: 10px 14px; border-radius: 8px; margin-top: 12px; display: none; font-size: 13px; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; z-index: 100; }
        .modal.show { display: flex; }
        .modal-card { background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 12px; padding: 28px; width: 100%; max-width: 360px; }
        .modal-card h3 { margin-bottom: 16px; }
        .modal-actions { display: flex; gap: 12px; margin-top: 20px; }
        .btn-cancel { background: #242424; color: #9e9e9e; flex: 1; }
        .btn-save   { background: #2d6ef5; color: #fff; flex: 1; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="header">
        <div class="page-title">🏷️ Categories</div>
    </div>

    <div class="content">
        <!-- Add Category -->
        <div class="card">
            <h2>Add New Category</h2>
            <label>CATEGORY NAME</label>
            <input type="text" id="newCat" placeholder="e.g. Science Fiction" />
            <div class="success" id="addSuccess">✓ Category added!</div>
            <button class="btn btn-primary" onclick="addCategory()">Add Category</button>
        </div>

        <!-- List -->
        <div class="card">
            <h2>All Categories</h2>
            <div id="catList">Loading...</div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-card">
        <h3>Edit Category</h3>
        <input type="hidden" id="editId" />
        <label>CATEGORY NAME</label>
        <input type="text" id="editName" />
        <div class="modal-actions">
            <button class="btn btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn btn-save"   onclick="saveEdit()">Save</button>
        </div>
    </div>
</div>

<script>
async function loadCategories() {
    const res  = await fetch('/bookstore_api/api/admin/admin_categories.php');
    const data = await res.json();
    const list = document.getElementById('catList');

    if (!data.categories?.length) {
        list.innerHTML = '<p style="color:#9e9e9e;font-size:14px">No categories yet.</p>';
        return;
    }

    list.innerHTML = data.categories.map(c => `
        <div class="cat-item">
            <span class="cat-name">${c.c_name}</span>
            <div class="cat-actions">
                <button class="btn-edit"   onclick="openEdit(${c.category_id}, '${c.c_name}')">Edit</button>
                <button class="btn-danger" onclick="deleteCategory(${c.category_id})">Delete</button>
            </div>
        </div>
    `).join('');
}

async function addCategory() {
    const name = document.getElementById('newCat').value.trim();
    if (!name) return;

    await fetch('/bookstore_api/api/admin/admin_categories.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ c_name: name })
    });

    document.getElementById('newCat').value = '';
    document.getElementById('addSuccess').style.display = 'block';
    setTimeout(() => document.getElementById('addSuccess').style.display = 'none', 3000);
    loadCategories();
}

async function deleteCategory(id) {
    if (!confirm('Delete this category? Books using it may be affected.')) return;
    await fetch('/bookstore_api/api/admin/admin_categories.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category_id: id })
    });
    loadCategories();
}

function openEdit(id, name) {
    document.getElementById('editId').value   = id;
    document.getElementById('editName').value = name;
    document.getElementById('editModal').classList.add('show');
}

function closeModal() {
    document.getElementById('editModal').classList.remove('show');
}

async function saveEdit() {
    const id   = document.getElementById('editId').value;
    const name = document.getElementById('editName').value.trim();
    if (!name) return;

    await fetch('/bookstore_api/api/admin/admin_categories.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category_id: id, c_name: name })
    });

    closeModal();
    loadCategories();
}

loadCategories();
</script>
</body>
</html>