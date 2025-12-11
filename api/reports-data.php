<?php
require_once __DIR__ . '/../helpers.php';
require_login_json(['admin','manager']);

$db = db();
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$stmt = $db->prepare('
    SELECT t.id, t.total_amount, t.cash_received, t.change_given, t.created_at, u.name AS cashier
    FROM transactions t
    JOIN users u ON u.id = t.user_id
    WHERE CAST(t.created_at AS DATE) BETWEEN :from AND :to
    ORDER BY t.created_at DESC
');
$stmt->execute([
    'from' => $from,
    'to' => $to,
]);
$rows = $stmt->fetchAll();
$totalSales = array_sum(array_column($rows, 'total_amount'));

respond_json([
    'from' => $from,
    'to' => $to,
    'rows' => $rows,
    'totalSales' => (float) $totalSales,
    'user' => current_user(),
]);

