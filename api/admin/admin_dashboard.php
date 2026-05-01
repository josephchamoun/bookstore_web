<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/admin_auth.php';

requireAdminAuth();

$orders = fsGetCollection('orders');
$books  = fsGetCollection('books');
$users  = fsGetCollection('users');

$totalRevenue   = 0;
$pendingOrders  = 0;
$ordersByStatus = [];
$salesByBook    = [];
$spendByUser    = [];
$revenueByMonth = [];
$sixMonthsAgo   = new DateTime('-6 months');

foreach ($orders as $order) {
    $status = $order['status'] ?? 'pending';
    $total  = (float)($order['total'] ?? 0);

    if ($status !== 'cancelled') $totalRevenue += $total;
    if ($status === 'pending')   $pendingOrders++;

    $ordersByStatus[$status] = ($ordersByStatus[$status] ?? 0) + 1;

    // Revenue by month
    $orderDate = $order['orderDate'] ?? '';
    if ($orderDate && $status !== 'cancelled') {
        try {
            $date = new DateTime($orderDate);
            if ($date >= $sixMonthsAgo) {
                $monthKey   = $date->format('Y-m');
                $monthLabel = $date->format('M Y');
                if (!isset($revenueByMonth[$monthKey])) {
                    $revenueByMonth[$monthKey] = [
                        'month'       => $monthLabel,
                        'month_key'   => $monthKey,
                        'revenue'     => 0,
                        'order_count' => 0,
                    ];
                }
                $revenueByMonth[$monthKey]['revenue']     += $total;
                $revenueByMonth[$monthKey]['order_count'] += 1;
            }
        } catch (Exception $e) {}
    }

    // Per-book and per-category sales
    foreach ($order['items'] ?? [] as $item) {
        if ($status === 'cancelled') continue;
        $bookId  = $item['bookId']    ?? '';
        $qty     = (int)($item['quantity']  ?? 0);
        $price   = (float)($item['unitPrice'] ?? 0);
        if (!$bookId) continue;

        // Per-book
        if (!isset($salesByBook[$bookId])) {
            $salesByBook[$bookId] = [
                'book_id'       => $bookId,
                'title'         => $item['bookTitle'] ?? '',
                'coverUrl'      => $item['coverUrl']  ?? '',
                'total_sold'    => 0,
                'total_revenue' => 0,
            ];
        }
        $salesByBook[$bookId]['total_sold']    += $qty;
        $salesByBook[$bookId]['total_revenue'] += $qty * $price;

        // ← ADDED: Per-category
        $catName = '';
        foreach ($books as $b) {
            if ($b['id'] === $bookId) {
                $catName = $b['categoryName'] ?? '';
                break;
            }
        }
        if ($catName) {
            if (!isset($salesByCat[$catName])) {
                $salesByCat[$catName] = [
                    'name'          => $catName,
                    'total_sold'    => 0,
                    'total_revenue' => 0,
                ];
            }
            $salesByCat[$catName]['total_sold']    += $qty;
            $salesByCat[$catName]['total_revenue'] += $qty * $price;
        }
    }

    // Per-user spending
    $userId = $order['userId'] ?? '';
    if ($userId && $status !== 'cancelled') {
        $spendByUser[$userId] = ($spendByUser[$userId] ?? 0) + $total;
    }
}

// Best selling books
usort($salesByBook, fn($a, $b) => $b['total_sold'] - $a['total_sold']);
$bestSellingBooks = array_slice(array_values($salesByBook), 0, 5);

// Best buyers
arsort($spendByUser);
$bestBuyers = [];
$count = 0;
foreach ($spendByUser as $userId => $spent) {
    if ($count >= 5) break;
    $user = fsGetDocument('users', $userId);
    $bestBuyers[] = [
        'user_id'     => $userId,
        'u_name'      => $user['name']  ?? '',
        'u_email'     => $user['email'] ?? '',
        'total_spent' => $spent,
    ];
    $count++;
}

// Recent orders
usort($orders, fn($a, $b) => strcmp($b['orderDate'] ?? '', $a['orderDate'] ?? ''));
$recentOrders = array_slice($orders, 0, 5);
foreach ($recentOrders as &$o) {
    $o['order_id']   = $o['id'];
    $o['order_date'] = $o['orderDate'] ?? '';
    $user = fsGetDocument('users', $o['userId'] ?? '');
    $o['u_name']  = $user['name']  ?? '';
    $o['u_email'] = $user['email'] ?? '';
}

ksort($revenueByMonth);

$formattedStatus = [];
foreach ($ordersByStatus as $status => $count) {
    $formattedStatus[] = ['status' => $status, 'count' => $count];
}

echo json_encode([
    'stats' => [
        'total_revenue'  => round($totalRevenue, 2),
        'total_orders'   => count($orders),
        'total_books'    => count($books),
        'total_users'    => count($users),
        'pending_orders' => $pendingOrders,
        'total_stock'    => array_sum(array_column($books, 'stock')),
    ],
    'revenue_by_month'   => array_values($revenueByMonth),
    'orders_by_status'   => $formattedStatus,
    'best_selling_books' => $bestSellingBooks,
    'best_buyers'        => $bestBuyers,
    'best_categories' => array_values($salesByCat),
    'recent_orders'      => $recentOrders,
]);