<?php
require_once __DIR__ . '/helpers.php';
require_login(['admin','manager']);

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
?>
<?php include __DIR__ . '/partials/header.php'; ?>
<div class="brand-title fs-2 mb-3">Ledger Scrolls</div>

<form class="row gy-2 gx-3 align-items-end mb-4" method="get">
    <div class="col-md-3">
        <label class="form-label">From</label>
        <input type="date" class="form-control" name="from" value="<?= e($from) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">To</label>
        <input type="date" class="form-control" name="to" value="<?= e($to) ?>">
    </div>
    <div class="col-md-2">
        <button class="btn btn-brown" type="submit">Filter</button>
    </div>
    <div class="col-md-4 text-end">
        <div class="fw-bold">Total in range: <?= e(format_money($totalSales)) ?></div>
    </div>
</form>

<div class="card shadow">
    <div class="card-header">Sales</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Cashier</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Cash</th>
                    <th class="text-end">Change</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="6" class="text-center p-4 text-muted">No transactions.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= e($r['id']) ?></td>
                            <td><?= e($r['created_at']) ?></td>
                            <td><?= e($r['cashier']) ?></td>
                            <td class="text-end"><?= e(format_money($r['total_amount'])) ?></td>
                            <td class="text-end"><?= e(format_money($r['cash_received'])) ?></td>
                            <td class="text-end"><?= e(format_money($r['change_given'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>

