<?php require_once __DIR__ . '/../config/admin_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Users — BookStore Admin</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* ✅ IMPORTANT: match Orders page layout */
body {
    font-family: 'Segoe UI', sans-serif;
    background: #0d0d0d;
    color: #fff;
    display: flex;
    min-height: 100vh;
}

/* sidebar (same as other pages) */
.sidebar {
    width: 240px;
    background: #1a1a1a;
    border-right: 1px solid #2a2a2a;
    padding: 24px 0;
    position: fixed;
    height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ✅ FIX: match Orders page main layout */
.main {
    margin-left: 240px;
    flex: 1;
    padding: 32px;
}

/* header */
.header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.page-title {
    font-size: 22px;
    font-weight: 700;
}

/* filters */
.filters {
    display: flex;
    gap: 10px;
}

.filter-btn {
    padding: 7px 14px;
    border-radius: 20px;
    border: 1px solid #333;
    background: transparent;
    color: #aaa;
    cursor: pointer;
}

.filter-btn.active {
    background: #2d6ef5;
    color: #fff;
    border-color: #2d6ef5;
}

/* table */
.table-wrap {
    background: #1a1a1a;
    border-radius: 10px;
    overflow: hidden;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    text-align: left;
    padding: 12px;
    font-size: 11px;
    color: #777;
    background: #222;
}

td {
    padding: 14px 12px;
    border-bottom: 1px solid #222;
    font-size: 13px;
}

tr:hover td {
    background: #1e1e1e;
}

/* status */
.status {
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    display: inline-block;
}

.active {
    background: #1a3a1a;
    color: #4caf50;
}

.banned {
    background: #3a1a1a;
    color: #f44336;
}

/* buttons */
.btn {
    padding: 6px 10px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
}

.ban {
    background: #f44336;
    color: #fff;
}

.unban {
    background: #4caf50;
    color: #fff;
}

/* toast */
.toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #1a3a1a;
    border: 1px solid #4caf50;
    color: #4caf50;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 13px;
    display: none;
    z-index: 999;
}
/* missing sidebar styles */
.sidebar-logo {
    padding: 0 24px 24px;
    border-bottom: 1px solid #2a2a2a;
    font-size: 18px;
    font-weight: 700;
}

.sidebar-logo span { color: #2d6ef5; }

.nav { margin-top: 16px; flex: 1; }

.nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 24px;
    color: #9e9e9e;
    text-decoration: none;
    font-size: 14px;
}

.nav a:hover,
.nav a.active {
    background: #242424;
    color: #fff;
    border-left: 3px solid #2d6ef5;
}

.nav a .icon { font-size: 18px; width: 24px; }

.logout {
    padding: 16px 24px;
    border-top: 1px solid #2a2a2a;
}

.logout a { color: #f44336; text-decoration: none; font-size: 14px; }
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="header">
        <div class="page-title">👥 Users</div>

        <div class="filters">
            <button class="filter-btn active" onclick="filterUsers('all', this)">All</button>
            <button class="filter-btn" onclick="filterUsers('active', this)">Active</button>
            <button class="filter-btn" onclick="filterUsers('banned', this)">Banned</button>
        </div>
        <input type="text" id="searchInput" placeholder="Search by name or email..."
    oninput="renderUsers()"
    style="padding:7px 14px;border-radius:20px;border:1px solid #333;background:transparent;color:#fff;outline:none;width:220px">
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody id="usersBody">
                <tr>
                    <td colspan="4" style="text-align:center;color:#555;padding:40px">
                        Loading...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<div class="toast" id="toast">✓ Updated</div>

<script>
const adminToken = localStorage.getItem('admin_token');
if (!adminToken) window.location.href = '/bookstore_api/admin/login.php';

let allUsers = [];
let activeFilter = 'all';

async function api(url, options = {}) {
    return fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + adminToken,
            ...(options.headers || {})
        }
    });
}

/* LOAD USERS */
async function loadUsers() {
    const res = await api('/bookstore_api/api/admin/admin_users.php');
    const data = await res.json();
    allUsers = data.users || [];
    renderUsers();
}

/* FILTER */
function filterUsers(status, btn) {
    activeFilter = status;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderUsers();
}

/* RENDER */
function renderUsers() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const users = (activeFilter === 'all' ? allUsers : allUsers.filter(u => u.status === activeFilter))
        .filter(u => !query || u.u_name.toLowerCase().includes(query) || u.u_email.toLowerCase().includes(query));


    const tbody = document.getElementById('usersBody');

    if (!users.length) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:#555;padding:40px">No users found</td></tr>`;
        return;
    }

    tbody.innerHTML = users.map(u => `
        <tr>
            <td>${u.u_name}</td>
            <td>${u.u_email}</td>
            <td>
                <span class="status ${u.status}">
                    ${u.status}
                </span>
            </td>
            <td>
                ${u.status === 'active'
                    ? `<button class="btn ban" onclick="changeStatus(${u.user_id}, 'banned')">Ban</button>`
                    : `<button class="btn unban" onclick="changeStatus(${u.user_id}, 'active')">Unban</button>`
                }
            </td>
        </tr>
    `).join('');
}

/* BAN / UNBAN */
async function changeStatus(userId, status) {
    let ban_reason = null;

    if (status === 'banned') {
        ban_reason = prompt("Enter ban reason:");
        if (!ban_reason) return;
    }

    await api('/bookstore_api/api/admin/admin_users.php', {
        method: 'PUT',
        body: JSON.stringify({
            user_id: userId,
            status,
            ban_reason
        })
    });

    const user = allUsers.find(u => u.user_id === userId);
    if (user) {
        user.status = status;
        user.ban_reason = ban_reason;
    }

    renderUsers();
    showToast();
}

/* TOAST */
function showToast() {
    const t = document.getElementById('toast');
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 2000);
}

loadUsers();
</script>

</body>
</html>