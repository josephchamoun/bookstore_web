<?php require_once __DIR__ . '/../config/admin_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — BookStore Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0d0d0d;
            color: #fff;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
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
            transition: all 0.2s;
        }

        .nav a:hover, .nav a.active {
            background: #242424;
            color: #fff;
            border-left: 3px solid #2d6ef5;
        }

        .nav a .icon { font-size: 18px; width: 24px; }

        .logout {
            padding: 16px 24px;
            border-top: 1px solid #2a2a2a;
        }

        .logout a {
            color: #f44336;
            text-decoration: none;
            font-size: 14px;
        }

        /* Main */
        .main {
            margin-left: 240px;
            flex: 1;
            padding: 32px;
        }

        .page-title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: #9e9e9e;
            font-size: 14px;
            margin-bottom: 32px;
        }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            padding: 20px;
        }

        .stat-label {
            color: #9e9e9e;
            font-size: 12px;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2d6ef5;
        }

        .stat-sub {
            color: #9e9e9e;
            font-size: 12px;
            margin-top: 4px;
        }

        /* Quick links */
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .quick-card {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            padding: 20px;
            text-decoration: none;
            color: #fff;
            transition: border-color 0.2s;
            display: block;
        }

        .quick-card:hover { border-color: #2d6ef5; }
        .quick-card .icon { font-size: 28px; margin-bottom: 10px; }
        .quick-card h3 { font-size: 15px; margin-bottom: 4px; }
        .quick-card p  { color: #9e9e9e; font-size: 12px; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="page-title">Dashboard</div>
    <div class="page-subtitle">Welcome back, <?= $_SESSION['admin_username'] ?>!</div>

    <div class="stats" id="stats">
        <div class="stat-card">
            <div class="stat-label">TOTAL BOOKS</div>
            <div class="stat-value" id="statBooks">—</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">CATEGORIES</div>
            <div class="stat-value" id="statCats">—</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">TOTAL ORDERS</div>
            <div class="stat-value" id="statOrders">—</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">PENDING ORDERS</div>
            <div class="stat-value" id="statPending">—</div>
        </div>
    </div>

    <div class="quick-links">
        <a href="books.php" class="quick-card">
            <div class="icon">📚</div>
            <h3>Manage Books</h3>
            <p>Add, edit or remove books</p>
        </a>
        <a href="categories.php" class="quick-card">
            <div class="icon">🏷️</div>
            <h3>Categories</h3>
            <p>Manage book categories</p>
        </a>
        <a href="orders.php" class="quick-card">
            <div class="icon">📦</div>
            <h3>Orders</h3>
            <p>View and update orders</p>
        </a>
    </div>
</div>

<script>
async function loadStats() {
    const [books, cats, orders] = await Promise.all([
        fetch('/bookstore_api/api/admin/admin_books.php').then(r => r.json()),
        fetch('/bookstore_api/api/admin/admin_categories.php').then(r => r.json()),
        fetch('/bookstore_api/api/admin/admin_orders.php').then(r => r.json()),
    ]);

    document.getElementById('statBooks').textContent   = books.books?.length ?? 0;
    document.getElementById('statCats').textContent    = cats.categories?.length ?? 0;
    document.getElementById('statOrders').textContent  = orders.orders?.length ?? 0;
    document.getElementById('statPending').textContent = orders.orders?.filter(o => o.o_status === 'pending').length ?? 0;
}

loadStats();
</script>
</body>
</html>