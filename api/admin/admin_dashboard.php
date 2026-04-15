<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/admin_auth.php';

requireAdminAuth();

$pdo = getDB();

// --- General Stats ---
$totalRevenue = $pdo->query("
    SELECT COALESCE(SUM(o_total), 0) 
    FROM orders 
    WHERE o_status != 'cancelled'
")->fetchColumn();

$totalOrders  = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalBooks   = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalUsers   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE o_status = 'pending'")->fetchColumn();
$totalStock   = $pdo->query("SELECT COALESCE(SUM(b_stock), 0) FROM books")->fetchColumn();

// --- Revenue by Month (last 6 months) ---
$revenueByMonth = $pdo->query("
    SELECT 
        DATE_FORMAT(order_date, '%b %Y') AS month,
        DATE_FORMAT(order_date, '%Y-%m') AS month_key,
        COALESCE(SUM(o_total), 0)        AS revenue,
        COUNT(*)                          AS order_count
    FROM orders
    WHERE o_status != 'cancelled'
      AND order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month_key, month
    ORDER BY month_key ASC
")->fetchAll();

// --- Orders by Status ---
$ordersByStatus = $pdo->query("
    SELECT o_status AS status, COUNT(*) AS count
    FROM orders
    GROUP BY o_status
")->fetchAll();

// --- Best Selling Books ---
$bestSellingBooks = $pdo->query("
    SELECT 
        b.book_id,
        b.b_title,
        b.b_author,
        b.b_cover_url,
        SUM(oi.oi_quantity)              AS total_sold,
        SUM(oi.oi_quantity * oi.oi_unit_price) AS total_revenue
    FROM order_items oi
    JOIN books b ON oi.book_id = b.book_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.o_status != 'cancelled'
    GROUP BY b.book_id, b.b_title, b.b_author, b.b_cover_url
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll();

// --- Best Categories ---
$bestCategories = $pdo->query("
    SELECT 
        c.c_name,
        SUM(oi.oi_quantity)                    AS total_sold,
        SUM(oi.oi_quantity * oi.oi_unit_price) AS total_revenue
    FROM order_items oi
    JOIN books b      ON oi.book_id     = b.book_id
    JOIN categories c ON b.category_id  = c.category_id
    JOIN orders o     ON oi.order_id    = o.order_id
    WHERE o.o_status != 'cancelled'
    GROUP BY c.c_name
    ORDER BY total_sold DESC
")->fetchAll();

// --- Best Buyers ---
$bestBuyers = $pdo->query("
    SELECT 
        u.user_id,
        u.u_name,
        u.u_email,
        COUNT(o.order_id)      AS total_orders,
        SUM(o.o_total)         AS total_spent
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.o_status != 'cancelled'
    GROUP BY u.user_id, u.u_name, u.u_email
    ORDER BY total_spent DESC
    LIMIT 5
")->fetchAll();

// --- Recent Orders ---
$recentOrders = $pdo->query("
    SELECT 
        o.order_id,
        o.o_total,
        o.o_status,
        o.order_date,
        u.u_name,
        u.u_email
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    ORDER BY o.order_date DESC
    LIMIT 5
")->fetchAll();

echo json_encode([
    'stats' => [
        'total_revenue'  => (float) $totalRevenue,
        'total_orders'   => (int)   $totalOrders,
        'total_books'    => (int)   $totalBooks,
        'total_users'    => (int)   $totalUsers,
        'pending_orders' => (int)   $pendingOrders,
        'total_stock'    => (int)   $totalStock,
    ],
    'revenue_by_month'  => $revenueByMonth,
    'orders_by_status'  => $ordersByStatus,
    'best_selling_books'=> $bestSellingBooks,
    'best_categories'   => $bestCategories,
    'best_buyers'       => $bestBuyers,
    'recent_orders'     => $recentOrders,
]);