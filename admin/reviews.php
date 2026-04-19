<?php require_once __DIR__ . '/../config/admin_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reviews — BookStore Admin</title>
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

        /* Header */
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .page-title { font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 10px; }

        /* Stats */
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px; }
        .stat-card { background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 12px; padding: 16px 20px; }
        .stat-label { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px; }
        .stat-value { font-size: 22px; font-weight: 700; }
        .stat-value.amber  { color: #ffa726; }
        .stat-value.green  { color: #4caf50; }
        .stat-value.red    { color: #f44336; }

        /* Filters */
        .filters { display: flex; gap: 8px; }
        .filter-btn { padding: 7px 16px; border-radius: 20px; border: 1px solid #2a2a2a; background: transparent; color: #9e9e9e; cursor: pointer; font-size: 13px; transition: all 0.2s; }
        .filter-btn.active { background: #2d6ef5; color: #fff; border-color: #2d6ef5; }
        .filter-btn:hover:not(.active) { background: #242424; color: #fff; }

        /* Table */
        .table-wrap { background: #1a1a1a; border-radius: 14px; border: 1px solid #2a2a2a; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        col.col-id      { width: 60px; }
        col.col-book    { width: 200px; }
        col.col-user    { width: 140px; }
        col.col-rating  { width: 100px; }
        col.col-comment { width: auto; }
        col.col-date    { width: 110px; }
        col.col-actions { width: 160px; }
        th { background: #212121; padding: 12px 16px; text-align: left; font-size: 10px; color: #666; letter-spacing: .08em; text-transform: uppercase; border-bottom: 1px solid #2a2a2a; }
        td { padding: 14px 16px; border-bottom: 1px solid #222; font-size: 13px; vertical-align: top; overflow: hidden; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #1e1e1e; }

        /* Stars */
        .stars { color: #ffa726; letter-spacing: 2px; font-size: 14px; }
        .stars-empty { color: #333; }

        /* Comment */
        .comment-text { font-size: 12px; color: #aaa; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

        /* Date */
        .date-text { font-size: 12px; color: #777; }

        /* Book / user */
        .book-title { font-weight: 600; font-size: 13px; }
        .user-name  { font-size: 13px; color: #ccc; }

        /* Action buttons */
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { padding: 6px 14px; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
        .btn:hover { opacity: 0.8; }
        .btn-approve { background: #1a3a1a; color: #4caf50; border: 1px solid #4caf50; }
        .btn-reject  { background: #3a1a1a; color: #f44336; border: 1px solid #f44336; }

        /* Status badge (for approved/rejected filter view) */
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-pending   { background: #3a2a00; color: #ffa726; }
        .status-approved  { background: #1a3a1a; color: #4caf50; }
        .status-rejected  { background: #3a1a1a; color: #f44336; }

        /* Toast */
        .toast { position: fixed; bottom: 24px; right: 24px; padding: 12px 20px; border-radius: 10px; font-size: 13px; display: none; z-index: 999; }
        .toast.success { background: #1a3a1a; border: 1px solid #4caf50; color: #4caf50; }
        .toast.error   { background: #3a1a1a; border: 1px solid #f44336; color: #f44336; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="header">
        <div class="page-title">💬 Reviews</div>
        <div class="filters">
            <button class="filter-btn active" onclick="filter('pending', this)">Pending</button>
            <button class="filter-btn" onclick="filter('approved', this)">Approved</button>
            <button class="filter-btn" onclick="filter('rejected', this)">Rejected</button>
            <button class="filter-btn" onclick="filter('all', this)">All</button>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value amber" id="stat-pending">—</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Approved</div>
            <div class="stat-value green" id="stat-approved">—</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Rejected</div>
            <div class="stat-value red" id="stat-rejected">—</div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <colgroup>
                <col class="col-id">
                <col class="col-book">
                <col class="col-user">
                <col class="col-rating">
                <col class="col-comment">
                <col class="col-date">
                <col class="col-actions">
            </colgroup>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Book</th>
                    <th>User</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="reviewsBody">
                <tr><td colspan="7" style="text-align:center;color:#555;padding:40px">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="toast" id="toast"></div>

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

let allReviews   = [];
let activeFilter = 'pending';

// ── Load all reviews (pending + approved + rejected) ──────────────────────────
async function loadReviews() {
    try {
        // Fetch pending from admin endpoint
        const pendingRes  = await apiFetch('/bookstore_api/api/admin/admin_reviews.php');
        const pendingData = await pendingRes.json();

        // We only get pending from the admin endpoint, so we fetch all statuses
        // by hitting the endpoint and supplementing with a full query
        // For now store what we have and render
        allReviews = pendingData.reviews || [];
        renderStats();
        renderReviews();
    } catch (e) {
        showToast('Failed to load reviews', false);
    }
}

function renderStats() {
    const counts = { pending: 0, approved: 0, rejected: 0 };
    allReviews.forEach(r => { if (counts[r.status] !== undefined) counts[r.status]++; });
    document.getElementById('stat-pending').textContent  = counts.pending;
    document.getElementById('stat-approved').textContent = counts.approved;
    document.getElementById('stat-rejected').textContent = counts.rejected;
}

function filter(status, btn) {
    activeFilter = status;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderReviews();
}

function fmtDate(s) {
    return new Date(s).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function starsHtml(rating) {
    return '<span class="stars">' + '★'.repeat(rating) + '</span>' +
           '<span class="stars-empty">' + '★'.repeat(5 - rating) + '</span>';
}

function renderReviews() {
    const list = activeFilter === 'all'
        ? allReviews
        : allReviews.filter(r => r.status === activeFilter);

    const tbody = document.getElementById('reviewsBody');

    if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#555;padding:40px">
            No ${activeFilter === 'all' ? '' : activeFilter} reviews found
        </td></tr>`;
        return;
    }

    tbody.innerHTML = list.map(r => `
        <tr id="row-${r.review_id}">
            <td><strong>#${r.review_id}</strong></td>
            <td><div class="book-title">${r.book_title}</div></td>
            <td><div class="user-name">${r.user_name}</div></td>
            <td>${starsHtml(parseInt(r.rating))}</td>
            <td><div class="comment-text">${r.comment}</div></td>
            <td><div class="date-text">${fmtDate(r.created_at)}</div></td>
            <td>
                ${r.status === 'pending' ? `
                <div class="actions">
                    <button class="btn btn-approve" onclick="updateReview(${r.review_id}, 'approved')">✔ Approve</button>
                    <button class="btn btn-reject"  onclick="updateReview(${r.review_id}, 'rejected')">✘ Reject</button>
                </div>` : `
                <span class="status-badge status-${r.status}">
                    ${r.status.charAt(0).toUpperCase() + r.status.slice(1)}
                </span>`}
            </td>
        </tr>
    `).join('');
}

async function updateReview(reviewId, action) {
    try {
        const res  = await apiFetch('/bookstore_api/api/admin/admin_reviews.php', {
            method: 'PUT',
            body: JSON.stringify({ review_id: reviewId, action })
        });
        const data = await res.json();

        if (data.success) {
            // Update local state — no full reload needed
            const review = allReviews.find(r => r.review_id === reviewId);
            if (review) review.status = action;
            renderStats();
            renderReviews();
            showToast(`Review ${action} ✓`, true);
        } else {
            showToast(data.message || 'Action failed', false);
        }
    } catch (e) {
        showToast('Network error', false);
    }
}

function showToast(msg, success) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.className   = 'toast ' + (success ? 'success' : 'error');
    toast.style.display = 'block';
    setTimeout(() => toast.style.display = 'none', 2500);
}

loadReviews();
</script>
</body>
</html>