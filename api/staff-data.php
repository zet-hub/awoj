<?php
require_once __DIR__ . '/../helpers.php';
require_login_json(['admin','manager']);

$db = db();

$cashierStmt = $db->query("
    SELECT u.id, u.name, u.email, COUNT(t.id) AS tx_count, COALESCE(SUM(t.total_amount),0) AS tx_total
    FROM users u
    LEFT JOIN transactions t ON t.user_id = u.id AND t.status = 'completed'
    GROUP BY u.id, u.name, u.email
    ORDER BY tx_total DESC
");
$cashierStats = $cashierStmt->fetchAll();

$voidStmt = $db->query("
    SELECT vl.id, vl.transaction_id, vl.action, vl.reason, vl.created_at, a.name AS actor
    FROM void_logs vl
    JOIN users a ON a.id = vl.acted_by
    ORDER BY vl.created_at DESC
    OFFSET 0 ROWS FETCH NEXT 30 ROWS ONLY
");
$voids = $voidStmt->fetchAll();

$loginStmt = $db->query("
    SELECT TOP 30 al.created_at, u.name, u.email, al.ip_address, al.user_agent
    FROM access_logs al
    JOIN users u ON u.id = al.user_id
    ORDER BY al.created_at DESC
");
$logins = $loginStmt->fetchAll();

respond_json([
    'cashierStats' => $cashierStats,
    'voids' => $voids,
    'logins' => $logins,
    'user' => current_user(),
]);

