<?php
require_once __DIR__ . '/helpers.php';
require_login(['admin','manager']);

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
?>
<?php include __DIR__ . '/partials/header.php'; ?>
<div class="brand-title fs-2 mb-3">Staff Activity</div>

<div class="card shadow mb-4">
    <div class="card-header">Transactions by User</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th class="text-end">Orders</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$cashierStats): ?>
                    <tr><td colspan="4" class="text-center p-4 text-muted">No data.</td></tr>
                <?php else: ?>
                    <?php foreach ($cashierStats as $c): ?>
                        <tr>
                            <td><?= e($c['name']) ?></td>
                            <td><?= e($c['email']) ?></td>
                            <td class="text-end"><?= e($c['tx_count']) ?></td>
                            <td class="text-end"><?= e(format_money($c['tx_total'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow h-100">
            <div class="card-header">Void/Refund Actions (latest 30)</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Txn</th>
                            <th>Action</th>
                            <th>By</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$voids): ?>
                            <tr><td colspan="5" class="text-center p-4 text-muted">No actions.</td></tr>
                        <?php else: ?>
                            <?php foreach ($voids as $v): ?>
                                <tr>
                                    <td><?= e($v['id']) ?></td>
                                    <td><?= e($v['transaction_id']) ?></td>
                                    <td><?= e($v['action']) ?></td>
                                    <td><?= e($v['actor']) ?></td>
                                    <td><?= e($v['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow h-100">
            <div class="card-header">Recent Logins (latest 30)</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>User</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$logins): ?>
                            <tr><td colspan="3" class="text-center p-4 text-muted">No logins yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logins as $l): ?>
                                <tr>
                                    <td><?= e($l['created_at']) ?></td>
                                    <td><?= e($l['name']) ?> (<?= e($l['email']) ?>)</td>
                                    <td><?= e($l['ip_address']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>



