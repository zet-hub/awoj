<?php
require_once __DIR__ . '/../helpers.php';
require_login_json();

$db = db();

$todayTotal = $db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM transactions WHERE status = 'completed' AND CAST(created_at AS DATE) = CAST(GETDATE() AS DATE)")->fetchColumn();
$weekTotal = $db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM transactions WHERE status = 'completed' AND created_at >= DATEADD(day, -7, GETDATE())")->fetchColumn();
$monthTotal = $db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM transactions WHERE status = 'completed' AND MONTH(created_at) = MONTH(GETDATE()) AND YEAR(created_at) = YEAR(GETDATE())")->fetchColumn();
$orderCount = $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'completed'")->fetchColumn();
$avgOrder = $orderCount ? ($db->query("SELECT AVG(total_amount) FROM transactions WHERE status = 'completed'")->fetchColumn()) : 0;

$topItems = $db->query("
    SELECT TOP 5 mi.name, SUM(ti.quantity) AS qty
    FROM transaction_items ti
    JOIN menu_items mi ON mi.id = ti.menu_item_id
    JOIN transactions t ON t.id = ti.transaction_id
    WHERE t.status = 'completed'
    GROUP BY mi.name
    ORDER BY qty DESC
")->fetchAll();

$catSales = $db->query("
    SELECT c.name, SUM(ti.quantity * ti.price_each) AS total
    FROM transaction_items ti
    JOIN menu_items mi ON mi.id = ti.menu_item_id
    JOIN categories c ON c.id = mi.category_id
    JOIN transactions t ON t.id = ti.transaction_id
    WHERE t.status = 'completed'
    GROUP BY c.name
    ORDER BY total DESC
")->fetchAll();

$hourly = $db->query("
    SELECT DATEPART(HOUR, created_at) AS hr, SUM(total_amount) AS total
    FROM transactions
    WHERE status = 'completed' AND created_at >= DATEADD(day, -7, GETDATE())
    GROUP BY DATEPART(HOUR, created_at)
    ORDER BY hr
")->fetchAll();

$pm = $db->query("
    SELECT payment_method, COUNT(*) AS cnt, SUM(total_amount) AS total
    FROM transactions
    WHERE status = 'completed'
    GROUP BY payment_method
")->fetchAll();

respond_json([
    'todayTotal' => (float) $todayTotal,
    'weekTotal' => (float) $weekTotal,
    'monthTotal' => (float) $monthTotal,
    'orderCount' => (int) $orderCount,
    'avgOrder' => (float) $avgOrder,
    'topItems' => $topItems,
    'catSales' => $catSales,
    'hourly' => $hourly,
    'paymentMix' => $pm,
    'user' => current_user(),
]);

