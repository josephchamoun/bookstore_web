<?php require_once __DIR__ . '/../config/admin_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — BookStore Admin</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg:        #0a0a0f;
            --surface:   #111118;
            --surface2:  #1a1a24;
            --border:    #222230;
            --blue:      #3d6ef5;
            --blue-dim:  #1e2d5a;
            --green:     #22c55e;
            --green-dim: #14321e;
            --amber:     #f59e0b;
            --amber-dim: #32250a;
            --red:       #ef4444;
            --red-dim:   #3a1212;
            --purple:    #a855f7;
            --purple-dim:#2d1a40;
            --text:      #f0f0f8;
            --muted:     #6b6b80;
            --muted2:    #3a3a50;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: 240px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 24px 0;
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
            z-index: 10;
        }

        .sidebar-logo {
            padding: 0 24px 24px;
            border-bottom: 1px solid var(--border);
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .sidebar-logo span { color: var(--blue); }

        .nav { margin-top: 16px; flex: 1; }
        .nav a {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 24px;
            color: var(--muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }
        .nav a:hover, .nav a.active {
            background: var(--surface2);
            color: var(--text);
            border-left-color: var(--blue);
        }
        .nav a .icon { font-size: 17px; width: 22px; }

        .logout { padding: 16px 24px; border-top: 1px solid var(--border); }
        .logout a {
            color: var(--red);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
        }

        /* ── Main ── */
        .main { margin-left: 240px; flex: 1; padding: 32px 36px; }

        /* ── Header ── */
        .top-bar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 32px;
        }
        .page-title {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.8px;
        }
        .page-subtitle {
            color: var(--muted);
            font-size: 13px;
            margin-top: 4px;
        }
        .page-subtitle span { color: var(--blue); font-weight: 600; }

        .live-badge {
            display: flex; align-items: center; gap: 8px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 12px;
            color: var(--muted);
            font-family: 'DM Mono', monospace;
        }
        .live-dot {
            width: 7px; height: 7px;
            background: var(--green);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.3; }
        }

        /* ── Stat Cards ── */
        .stats {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.2s, transform 0.2s;
        }
        .stat-card:hover { border-color: var(--blue); transform: translateY(-2px); }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 60px; height: 60px;
            border-radius: 0 14px 0 60px;
            opacity: 0.12;
        }
        .stat-card.blue::before   { background: var(--blue); }
        .stat-card.green::before  { background: var(--green); }
        .stat-card.amber::before  { background: var(--amber); }
        .stat-card.red::before    { background: var(--red); }
        .stat-card.purple::before { background: var(--purple); }
        .stat-card.teal::before   { background: #14b8a6; }

        .stat-icon {
            font-size: 20px;
            margin-bottom: 12px;
        }
        .stat-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1.2px;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .stat-value {
            font-size: 26px;
            font-weight: 700;
            font-family: 'DM Mono', monospace;
            letter-spacing: -1px;
        }
        .stat-card.blue   .stat-value { color: var(--blue); }
        .stat-card.green  .stat-value { color: var(--green); }
        .stat-card.amber  .stat-value { color: var(--amber); }
        .stat-card.red    .stat-value { color: var(--red); }
        .stat-card.purple .stat-value { color: var(--purple); }
        .stat-card.teal   .stat-value { color: #14b8a6; }

        /* ── Grid Layouts ── */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .grid-3 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        .grid-equal { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }

        /* ── Cards ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 14px;
            font-weight: 600;
            letter-spacing: -0.3px;
        }
        .card-badge {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            background: var(--surface2);
            color: var(--muted);
            font-family: 'DM Mono', monospace;
        }

        /* ── Chart wrappers ── */
        .chart-wrap { position: relative; }

        /* ── Best Sellers Table ── */
        .rank-list { display: flex; flex-direction: column; gap: 12px; }
        .rank-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px;
            background: var(--surface2);
            border-radius: 10px;
            border: 1px solid var(--border);
            transition: border-color 0.15s;
        }
        .rank-item:hover { border-color: var(--blue); }

        .rank-num {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            background: var(--blue-dim);
            color: var(--blue);
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-family: 'DM Mono', monospace;
        }
        .rank-num.gold   { background: #32250a; color: var(--amber); }
        .rank-num.silver { background: #1e2530; color: #94a3b8; }
        .rank-num.bronze { background: #2a1a10; color: #cd7c54; }

        .rank-cover {
            width: 36px; height: 50px;
            object-fit: cover;
            border-radius: 4px;
            background: var(--surface2);
            flex-shrink: 0;
        }
        .rank-info { flex: 1; min-width: 0; }
        .rank-title {
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .rank-sub {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
        }
        .rank-stat {
            text-align: right;
            flex-shrink: 0;
        }
        .rank-stat .val {
            font-size: 13px;
            font-weight: 700;
            font-family: 'DM Mono', monospace;
            color: var(--green);
        }
        .rank-stat .sub {
            font-size: 10px;
            color: var(--muted);
        }

        /* ── Buyers ── */
        .buyer-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }
        .buyer-item:last-child { border-bottom: none; }

        .buyer-avatar {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: var(--blue-dim);
            color: var(--blue);
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .buyer-info { flex: 1; min-width: 0; }
        .buyer-name {
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .buyer-email {
            font-size: 11px;
            color: var(--muted);
        }
        .buyer-stat {
            text-align: right;
        }
        .buyer-spent {
            font-size: 13px;
            font-weight: 700;
            font-family: 'DM Mono', monospace;
            color: var(--amber);
        }
        .buyer-orders {
            font-size: 10px;
            color: var(--muted);
        }

        /* ── Recent Orders ── */
        .order-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }
        .order-item:last-child { border-bottom: none; }

        .order-id {
            font-family: 'DM Mono', monospace;
            font-size: 12px;
            color: var(--blue);
            width: 40px;
            flex-shrink: 0;
        }
        .order-info { flex: 1; min-width: 0; }
        .order-name {
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .order-date { font-size: 11px; color: var(--muted); }
        .order-total {
            font-family: 'DM Mono', monospace;
            font-size: 13px;
            font-weight: 600;
            color: var(--green);
            flex-shrink: 0;
        }

        .status-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            flex-shrink: 0;
        }
        .status-pending   { background: var(--amber-dim);  color: var(--amber); }
        .status-shipped   { background: var(--blue-dim);   color: var(--blue); }
        .status-delivered { background: var(--green-dim);  color: var(--green); }
        .status-cancelled { background: var(--red-dim);    color: var(--red); }

        /* ── Loading skeleton ── */
        .skeleton {
            background: linear-gradient(90deg, var(--surface2) 25%, var(--border) 50%, var(--surface2) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 6px;
            height: 20px;
        }
        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* ── Fade in ── */
        .fade-in {
            opacity: 0;
            transform: translateY(12px);
            animation: fadeUp 0.4s ease forwards;
        }
        @keyframes fadeUp {
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in:nth-child(1) { animation-delay: 0.05s; }
        .fade-in:nth-child(2) { animation-delay: 0.10s; }
        .fade-in:nth-child(3) { animation-delay: 0.15s; }
        .fade-in:nth-child(4) { animation-delay: 0.20s; }
        .fade-in:nth-child(5) { animation-delay: 0.25s; }
        .fade-in:nth-child(6) { animation-delay: 0.30s; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">

    <!-- Top Bar -->
    <div class="top-bar">
        <div>
            <div class="page-title">Dashboard</div>
            <div class="page-subtitle">Welcome back, <span id="adminName">—</span></div>
        </div>
        <div class="live-badge">
            <div class="live-dot"></div>
            <span id="liveTime">—</span>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="stats" id="statsGrid">
        <div class="stat-card green fade-in">
            <div class="stat-icon">💰</div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value" id="statRevenue">—</div>
        </div>
        <div class="stat-card blue fade-in">
            <div class="stat-icon">📦</div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-value" id="statOrders">—</div>
        </div>
        <div class="stat-card amber fade-in">
            <div class="stat-icon">⏳</div>
            <div class="stat-label">Pending</div>
            <div class="stat-value" id="statPending">—</div>
        </div>
        <div class="stat-card purple fade-in">
            <div class="stat-icon">📚</div>
            <div class="stat-label">Books</div>
            <div class="stat-value" id="statBooks">—</div>
        </div>
        <div class="stat-card teal fade-in">
            <div class="stat-icon">👤</div>
            <div class="stat-label">Users</div>
            <div class="stat-value" id="statUsers">—</div>
        </div>
        <div class="stat-card red fade-in">
            <div class="stat-icon">🏷️</div>
            <div class="stat-label">Stock Units</div>
            <div class="stat-value" id="statStock">—</div>
        </div>
    </div>

    <!-- Revenue Line + Orders Donut -->
    <div class="grid-3">
        <div class="card">
            <div class="card-header">
                <div class="card-title">📈 Revenue — Last 6 Months</div>
                <div class="card-badge" id="revenueTotal">—</div>
            </div>
            <div class="chart-wrap">
                <canvas id="revenueChart" height="110"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">🍩 Orders by Status</div>
            </div>
            <div class="chart-wrap" style="display:flex;align-items:center;justify-content:center;">
                <canvas id="statusChart" height="180" style="max-width:180px"></canvas>
            </div>
            <div id="statusLegend" style="margin-top:16px;display:flex;flex-direction:column;gap:8px;"></div>
        </div>
    </div>

    <!-- Best Sellers + Best Categories -->
    <div class="grid-2">
        <div class="card">
            <div class="card-header">
                <div class="card-title">🏆 Best Selling Books</div>
                <div class="card-badge">Top 5</div>
            </div>
            <div class="rank-list" id="bestBooks">
                <div class="skeleton"></div>
                <div class="skeleton" style="height:60px"></div>
                <div class="skeleton" style="height:60px"></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">📊 Sales by Category</div>
            </div>
            <div class="chart-wrap">
                <canvas id="categoryChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Best Buyers + Recent Orders -->
    <div class="grid-2">
        <div class="card">
            <div class="card-header">
                <div class="card-title">⭐ Best Buyers</div>
                <div class="card-badge">Top 5</div>
            </div>
            <div id="bestBuyers"></div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">🕐 Recent Orders</div>
                <a href="orders.php" style="font-size:12px;color:var(--blue);text-decoration:none;">View all →</a>
            </div>
            <div id="recentOrders"></div>
        </div>
    </div>

</div>

<script>
// ── Auth ──
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

// ── Admin name from JWT ──
function getAdminName() {
    try {
        return JSON.parse(atob(adminToken.split('.')[1])).username;
    } catch { return 'Admin'; }
}
document.getElementById('adminName').textContent = getAdminName();

// ── Live clock ──
function updateClock() {
    document.getElementById('liveTime').textContent =
        new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
updateClock();
setInterval(updateClock, 1000);

// ── Chart defaults ──
Chart.defaults.color          = '#6b6b80';
Chart.defaults.borderColor    = '#222230';
Chart.defaults.font.family    = 'DM Sans';

// ── Load everything ──
async function loadDashboard() {
    const res  = await apiFetch('/bookstore_api/api/admin/admin_dashboard.php');
    const data = await res.json();

    renderStats(data.stats);
    renderRevenueChart(data.revenue_by_month);
    renderStatusChart(data.orders_by_status);
    renderBestBooks(data.best_selling_books);
    renderCategoryChart(data.best_categories);
    renderBestBuyers(data.best_buyers);
    renderRecentOrders(data.recent_orders);
}

// ── Stats ──
function renderStats(s) {
    document.getElementById('statRevenue').textContent = '$' + parseFloat(s.total_revenue).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('statOrders').textContent  = s.total_orders;
    document.getElementById('statPending').textContent = s.pending_orders;
    document.getElementById('statBooks').textContent   = s.total_books;
    document.getElementById('statUsers').textContent   = s.total_users;
    document.getElementById('statStock').textContent   = s.total_stock;
}

// ── Revenue Line Chart ──
function renderRevenueChart(months) {
    const labels   = months.map(m => m.month);
    const revenues = months.map(m => parseFloat(m.revenue));
    const total    = revenues.reduce((a, b) => a + b, 0);

    document.getElementById('revenueTotal').textContent =
        '$' + total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});

    const ctx = document.getElementById('revenueChart').getContext('2d');

    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0,   'rgba(61, 110, 245, 0.3)');
    gradient.addColorStop(1,   'rgba(61, 110, 245, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Revenue',
                data: revenues,
                borderColor: '#3d6ef5',
                backgroundColor: gradient,
                borderWidth: 2.5,
                pointBackgroundColor: '#3d6ef5',
                pointBorderColor: '#0a0a0f',
                pointBorderWidth: 2,
                pointRadius: 5,
                tension: 0.4,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' $' + ctx.parsed.y.toFixed(2)
                    }
                }
            },
            scales: {
                x: { grid: { color: '#1a1a24' } },
                y: {
                    grid: { color: '#1a1a24' },
                    ticks: { callback: v => '$' + v }
                }
            }
        }
    });
}

// ── Status Donut ──
function renderStatusChart(statuses) {
    const colorMap = {
        pending:   { color: '#f59e0b', bg: '#32250a' },
        shipped:   { color: '#3d6ef5', bg: '#1e2d5a' },
        delivered: { color: '#22c55e', bg: '#14321e' },
        cancelled: { color: '#ef4444', bg: '#3a1212' },
    };

    const labels = statuses.map(s => s.status);
    const counts = statuses.map(s => parseInt(s.count));
    const colors = labels.map(l => colorMap[l]?.color || '#6b6b80');

    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: counts,
                backgroundColor: colors,
                borderColor: '#111118',
                borderWidth: 3,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            cutout: '68%',
            plugins: { legend: { display: false } }
        }
    });

    // Custom legend
    const legend = document.getElementById('statusLegend');
    legend.innerHTML = statuses.map(s => `
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:8px;height:8px;border-radius:2px;background:${colorMap[s.status]?.color || '#6b6b80'}"></div>
                <span style="font-size:12px;text-transform:capitalize">${s.status}</span>
            </div>
            <span style="font-size:12px;font-family:'DM Mono',monospace;color:var(--muted)">${s.count}</span>
        </div>
    `).join('');
}

// ── Best Selling Books ──
function renderBestBooks(books) {
    const medals = ['gold', 'silver', 'bronze', '', ''];
    document.getElementById('bestBooks').innerHTML = books.map((b, i) => `
        <div class="rank-item">
            <div class="rank-num ${medals[i]}">${i + 1}</div>
            <img class="rank-cover" src="${b.b_cover_url || ''}" onerror="this.style.display='none'" />
            <div class="rank-info">
                <div class="rank-title">${b.b_title}</div>
                <div class="rank-sub">${b.b_author}</div>
            </div>
            <div class="rank-stat">
                <div class="val">$${parseFloat(b.total_revenue).toFixed(2)}</div>
                <div class="sub">${b.total_sold} sold</div>
            </div>
        </div>
    `).join('');
}

// ── Category Bar Chart ──
function renderCategoryChart(cats) {
    const labels   = cats.map(c => c.c_name);
    const revenues = cats.map(c => parseFloat(c.total_revenue));

    const colors = ['#3d6ef5','#22c55e','#f59e0b','#a855f7','#14b8a6','#ef4444'];

    new Chart(document.getElementById('categoryChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Revenue',
                data: revenues,
                backgroundColor: labels.map((_, i) => colors[i % colors.length] + '99'),
                borderColor:     labels.map((_, i) => colors[i % colors.length]),
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' $' + ctx.parsed.y.toFixed(2) } }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    grid: { color: '#1a1a24' },
                    ticks: { callback: v => '$' + v }
                }
            }
        }
    });
}

// ── Best Buyers ──
function renderBestBuyers(buyers) {
    document.getElementById('bestBuyers').innerHTML = buyers.map(b => `
        <div class="buyer-item">
            <div class="buyer-avatar">${b.u_name.charAt(0).toUpperCase()}</div>
            <div class="buyer-info">
                <div class="buyer-name">${b.u_name}</div>
                <div class="buyer-email">${b.u_email}</div>
            </div>
            <div class="buyer-stat">
                <div class="buyer-spent">$${parseFloat(b.total_spent).toFixed(2)}</div>
                <div class="buyer-orders">${b.total_orders} orders</div>
            </div>
        </div>
    `).join('');
}

// ── Recent Orders ──
function renderRecentOrders(orders) {
    document.getElementById('recentOrders').innerHTML = orders.map(o => `
        <div class="order-item">
            <div class="order-id">#${o.order_id}</div>
            <div class="order-info">
                <div class="order-name">${o.u_name}</div>
                <div class="order-date">${o.order_date}</div>
            </div>
            <div class="order-total">$${parseFloat(o.o_total).toFixed(2)}</div>
            <div class="status-badge status-${o.o_status}">${o.o_status}</div>
        </div>
    `).join('');
}

loadDashboard();
</script>
</body>
</html>