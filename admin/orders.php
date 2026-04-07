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
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .page-title { font-size: 26px; font-weight: 700; }
        .filters { display: flex; gap: 8px; }
        .filter-btn { padding: 8px 18px; border-radius: 20px; border: 1px solid #2a2a2a; background: transparent; color: #9e9e9e; cursor: pointer; font-size: 13px; transition: all 0.2s; }
        .filter-btn.active { background: #2d6ef5; color: #fff; border-color: #2d6ef5; }
        table { width: 100%; border-collapse: collapse; background: #1a1a1a; border-radius: 12px; overflow: hidden; }
        th { background: #242424; padding: 14px 16px; text-align: left; font-size: 11px; color: #9e9e9e; letter-spacing: 1px; }
        td { padding: 14px 16px; border-bottom: 1px solid #2a2a2a; font-size: 14px; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #1f1f1f; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-pending   { background: #3a2a00; color: #ffa726; }
        .status-shipped   { background: #1a2a3a; color: #42a5f5; }
        .status-delivered { background: #1a3a1a; color: #4caf50; }
        .status-cancelled { background: #3a1a1a; color: #f44336; }
        select.status-select { padding: 6px 10px; background: #242424; border: 1px solid #2a2a2a; border-radius: 6px; color: #fff; font-size: 12px; cursor: pointer; outline: none; }
        .items-list { font-size: 12px; color: #9e9e9e; margin-top: 4px; }
        .toast { position: fixed; bottom: 24px; right: 24px; background: #1a3a1a; border: 1px solid #4caf50; color: #4caf50; padding: 12px 20px; border-radius: 8px; font-size: 14px; display: none; }
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

    <table>
        <thead>
            <tr>
                <th>ORDER</th>
                <th>CUSTOMER</th>
                <th>ITEMS</th>
                <th>TOTAL</th>
                <th>DATE</th>
                <th>STATUS</th>
            </tr>
        </thead>
        <tbody id="ordersBody">
            <tr><td colspan="6" style="text-align:center;color:#9e9e9e;padding:40px">Loading...</td></tr>
        </tbody>
    </table>
</div>

<div class="toast" id="toast">✓ Status updated</div>

<script>
let allOrders    = [];
let activeFilter = 'all';

async function loadOrders() {
    const res  = await fetch('/bookstore_api/api/admin/admin_orders.php');
    const data = await res.json();
    allOrders  = data.orders || [];
    renderOrders();
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
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#9e9e9e;padding:40px">No orders found</td></tr>';
        return;
    }

    tbody.innerHTML = orders.map(o => `
        <tr>
            <td><strong>#${o.order_id}</strong></td>
            <td>
                <div>${o.u_name}</div>
                <div style="color:#9e9e9e;font-size:12px">${o.u_email}</div>
            </td>
            <td>
                <div>${o.items?.length ?? 0} item(s)</div>
                <div class="items-list">${o.items?.map(i => i.b_title).join(', ') ?? ''}</div>
            </td>
            <td style="color:#2d6ef5;font-weight:600">$${parseFloat(o.o_total).toFixed(2)}</td>
            <td style="color:#9e9e9e;font-size:12px">${o.order_date}</td>
            <td>
                <select class="status-select" onchange="updateStatus(${o.order_id}, this.value)">
                    <option value="pending"   ${o.o_status==='pending'   ? 'selected':''}>Pending</option>
                    <option value="shipped"   ${o.o_status==='shipped'   ? 'selected':''}>Shipped</option>
                    <option value="delivered" ${o.o_status==='delivered' ? 'selected':''}>Delivered</option>
                    <option value="cancelled" ${o.o_status==='cancelled' ? 'selected':''}>Cancelled</option>
                </select>
            </td>
        </tr>
    `).join('');
}

async function updateStatus(orderId, status) {
    await fetch('/bookstore_api/api/admin/admin_orders.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, status })
    });
    const toast = document.getElementById('toast');
    toast.style.display = 'block';
    setTimeout(() => toast.style.display = 'none', 2500);
    // Update local data
    const order = allOrders.find(o => o.order_id === orderId);
    if (order) order.o_status = status;
}

loadOrders();
</script>
</body>
</html>