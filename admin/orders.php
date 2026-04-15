<?php require_once __DIR__ . '/../config/admin_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders — BookStore Admin</title>
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
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
        .stat-card { background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 12px; padding: 16px 20px; }
        .stat-label { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px; }
        .stat-value { font-size: 22px; font-weight: 700; }
        .stat-value.blue { color: #2d6ef5; }
        .stat-value.amber { color: #ffa726; }
        .stat-value.green { color: #4caf50; }
        .stat-sub { font-size: 11px; color: #555; margin-top: 3px; }

        /* Filters */
        .filters { display: flex; gap: 8px; }
        .filter-btn { padding: 7px 16px; border-radius: 20px; border: 1px solid #2a2a2a; background: transparent; color: #9e9e9e; cursor: pointer; font-size: 13px; transition: all 0.2s; }
        .filter-btn.active { background: #2d6ef5; color: #fff; border-color: #2d6ef5; }
        .filter-btn:hover:not(.active) { background: #242424; color: #fff; }

        /* Table */
        .table-wrap { background: #1a1a1a; border-radius: 14px; border: 1px solid #2a2a2a; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        col.col-order   { width: 80px; }
        col.col-customer{ width: 220px; }
        col.col-items   { width: auto; }
        col.col-total   { width: 90px; }
        col.col-date    { width: 120px; }
        col.col-status  { width: 150px; }
        th { background: #212121; padding: 12px 16px; text-align: left; font-size: 10px; color: #666; letter-spacing: .08em; text-transform: uppercase; border-bottom: 1px solid #2a2a2a; }
        td { padding: 14px 16px; border-bottom: 1px solid #222; font-size: 13px; vertical-align: top; overflow: hidden; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #1e1e1e; }

        /* Customer cell */
        .cust-name  { font-weight: 600; font-size: 13px; }
        .cust-email { font-size: 11px; color: #777; margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .cust-addr  { font-size: 11px; color: #555; margin-top: 4px; display: flex; align-items: flex-start; gap: 4px; }
        .cust-addr svg { width: 11px; height: 11px; flex-shrink: 0; margin-top: 1px; stroke: #555; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

        /* Items cell */
        .items-count { font-size: 13px; font-weight: 600; }
        .items-list  { font-size: 11px; color: #666; margin-top: 4px; line-height: 1.5; }

        /* Total */
        .total { font-size: 14px; font-weight: 700; color: #2d6ef5; }

        /* Date */
        .date-text { font-size: 12px; color: #777; }

        /* Status */
        .status-select { padding: 6px 10px; background: #242424; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 12px; cursor: pointer; outline: none; width: 100%; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-top: 6px; }
        .status-pending   { background: #3a2a00; color: #ffa726; }
        .status-shipped   { background: #1a2a3a; color: #42a5f5; }
        .status-delivered { background: #1a3a1a; color: #4caf50; }
        .status-cancelled { background: #3a1a1a; color: #f44336; }

        /* Toast */
        .toast { position: fixed; bottom: 24px; right: 24px; background: #1a3a1a; border: 1px solid #4caf50; color: #4caf50; padding: 12px 20px; border-radius: 10px; font-size: 13px; display: none; z-index: 999; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="header">
        <div class="page-title">📦 Orders</div>
        <div class="filters">
            <button class="filter-btn active" onclick="filter('all', this)">All</button>
            <button class="filter-btn" onclick="filter('pending', this)">Pending</button>
            <button class="filter-btn" onclick="filter('shipped', this)">Shipped</button>
            <button class="filter-btn" onclick="filter('delivered', this)">Delivered</button>
            <button class="filter-btn" onclick="filter('cancelled', this)">Cancelled</button>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">Total Orders</div>
            <div class="stat-value" id="stat-total">—</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value amber" id="stat-pending">—</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Shipped</div>
            <div class="stat-value blue" id="stat-shipped">—</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Revenue</div>
            <div class="stat-value green" id="stat-revenue">—</div>
            <div class="stat-sub">excl. cancelled</div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <colgroup>
                <col class="col-order">
                <col class="col-customer">
                <col class="col-items">
                <col class="col-total">
                <col class="col-date">
                <col class="col-status">
            </colgroup>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="ordersBody">
                <tr><td colspan="6" style="text-align:center;color:#555;padding:40px">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="toast" id="toast">✓ Status updated</div>

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

let allOrders    = [];
let activeFilter = 'all';

async function loadOrders() {
    const res  = await apiFetch('/bookstore_api/api/admin/admin_orders.php');
    const data = await res.json();
    allOrders  = data.orders || [];
    renderStats();
    renderOrders();
}

function fmtDate(s) {
    const d = new Date(s);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function renderStats() {
    const counts = { pending: 0, shipped: 0, delivered: 0, cancelled: 0 };
    let revenue = 0;
    allOrders.forEach(o => {
        if (counts[o.o_status] !== undefined) counts[o.o_status]++;
        if (o.o_status !== 'cancelled') revenue += parseFloat(o.o_total);
    });
    document.getElementById('stat-total').textContent   = allOrders.length;
    document.getElementById('stat-pending').textContent = counts.pending;
    document.getElementById('stat-shipped').textContent = counts.shipped;
    document.getElementById('stat-revenue').textContent = '$' + revenue.toFixed(2);
}

function filter(status, btn) {
    activeFilter = status;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderOrders();
}

function renderOrders() {
    const orders = activeFilter === 'all'
        ? allOrders
        : allOrders.filter(o => o.o_status === activeFilter);

    const tbody = document.getElementById('ordersBody');

    if (!orders.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#555;padding:40px">No orders found</td></tr>';
        return;
    }

    tbody.innerHTML = orders.map(o => `
        <tr>
            <td><strong>#${o.order_id}</strong></td>
            <td>
                <div class="cust-name">${o.u_name}</div>
                <div class="cust-email">${o.u_email}</div>
                <div class="cust-addr">
                    <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    ${o.o_shipping_address || '—'}
                </div>
            </td>
            <td>
                <div class="items-count">${o.items?.length ?? 0} item${(o.items?.length ?? 0) !== 1 ? 's' : ''}</div>
                <div class="items-list">${o.items?.map(i => i.b_title).join(', ') || '—'}</div>
            </td>
            <td><span class="total">$${parseFloat(o.o_total).toFixed(2)}</span></td>
            <td><div class="date-text">${fmtDate(o.order_date)}</div></td>
            <td>
                <select class="status-select" onchange="updateStatus(${o.order_id}, this.value)">
                    <option value="pending"   ${o.o_status === 'pending'   ? 'selected' : ''}>Pending</option>
                    <option value="shipped"   ${o.o_status === 'shipped'   ? 'selected' : ''}>Shipped</option>
                    <option value="delivered" ${o.o_status === 'delivered' ? 'selected' : ''}>Delivered</option>
                    <option value="cancelled" ${o.o_status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                </select>
                <span class="status-badge status-${o.o_status}">
                    ${o.o_status.charAt(0).toUpperCase() + o.o_status.slice(1)}
                </span>
            </td>
        </tr>
    `).join('');
}

async function updateStatus(orderId, status) {
    await apiFetch('/bookstore_api/api/admin/admin_orders.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, status })
    });
    const order = allOrders.find(o => o.order_id === orderId);
    if (order) order.o_status = status;
    renderStats();
    renderOrders();
    const toast = document.getElementById('toast');
    toast.style.display = 'block';
    setTimeout(() => toast.style.display = 'none', 2500);
}

loadOrders();
</script>
</body>
</html>