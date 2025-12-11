<?php
require_once __DIR__ . '/helpers.php';
require_login();

$db = db();

$todayTotal = $db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM transactions WHERE status = 'completed' AND CAST(created_at AS DATE) = CAST(GETDATE() AS DATE)")->fetchColumn();
$weekTotal = $db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM transactions WHERE status = 'completed' AND created_at >= DATEADD(day, -7, GETDATE())")->fetchColumn();
$monthTotal = $db->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM transactions WHERE status = 'completed' AND MONTH(created_at) = MONTH(GETDATE()) AND YEAR(created_at) = YEAR(GETDATE())")->fetchColumn();
$orderCount = $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'completed'")->fetchColumn();
$avgOrder = $orderCount ? ($db->query("SELECT AVG(total_amount) FROM transactions WHERE status = 'completed'")->fetchColumn()) : 0;
$topItemsStmt = $db->query("
    SELECT TOP 5 mi.name, SUM(ti.quantity) AS qty
    FROM transaction_items ti
    JOIN menu_items mi ON mi.id = ti.menu_item_id
    JOIN transactions t ON t.id = ti.transaction_id
    WHERE t.status = 'completed'
    GROUP BY mi.name
    ORDER BY qty DESC
");
$topItems = $topItemsStmt->fetchAll();

$catStmt = $db->query("
    SELECT c.name, SUM(ti.quantity * ti.price_each) AS total
    FROM transaction_items ti
    JOIN menu_items mi ON mi.id = ti.menu_item_id
    JOIN categories c ON c.id = mi.category_id
    JOIN transactions t ON t.id = ti.transaction_id
    WHERE t.status = 'completed'
    GROUP BY c.name
    ORDER BY total DESC
");
$catSales = $catStmt->fetchAll();

$hourStmt = $db->query("
    SELECT DATEPART(HOUR, created_at) AS hr, SUM(total_amount) AS total
    FROM transactions
    WHERE status = 'completed' AND created_at >= DATEADD(day, -7, GETDATE())
    GROUP BY DATEPART(HOUR, created_at)
    ORDER BY hr
");
$hourly = $hourStmt->fetchAll();
?>
<?php include __DIR__ . '/partials/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="brand-title fs-2">Hall of Ledgers</div>
        <div class="text-muted">Minimal parchment, medieval accents, swift glance.</div>
    </div>
    <div>
        <a class="btn btn-brown" href="orders.php">Open Orders</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-3">
        <div class="muted-box h-100">
            <div class="text-muted">Today</div>
            <div class="fs-4 fw-bold"><?= e(format_money($todayTotal)) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="muted-box h-100">
            <div class="text-muted">Last 7 Days</div>
            <div class="fs-4 fw-bold"><?= e(format_money($weekTotal)) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="muted-box h-100">
            <div class="text-muted">This Month</div>
            <div class="fs-4 fw-bold"><?= e(format_money($monthTotal)) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="muted-box h-100">
            <div class="text-muted">Orders</div>
            <div class="fs-4 fw-bold"><?= e($orderCount) ?></div>
            <div class="small text-muted">Avg <?= e(format_money($avgOrder)) ?></div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-lg-6">
        <div class="card shadow h-100">
            <div class="card-header">Top 5 Bestsellers</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$topItems): ?>
                            <tr><td colspan="2" class="text-center p-4 text-muted">No sales yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topItems as $item): ?>
                                <tr>
                                    <td><?= e($item['name']) ?></td>
                                    <td class="text-end"><?= e($item['qty']) ?></td>
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
            <div class="card-header">Peak Hours (last 7 days)</div>
            <div class="card-body">
                <?php if (!$hourly): ?>
                    <div class="text-muted">No sales yet.</div>
                <?php else: ?>
                    <?php
                    $maxHourTotal = max(array_column($hourly, 'total'));
                    foreach ($hourly as $h):
                        $w = $maxHourTotal > 0 ? round(($h['total'] / $maxHourTotal) * 100) : 0;
                    ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span><?= str_pad($h['hr'], 2, '0', STR_PAD_LEFT) ?>:00</span>
                                <span><?= e(format_money($h['total'])) ?></span>
                            </div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar bg-brown" style="width: <?= $w ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-lg-6">
        <div class="card shadow h-100">
            <div class="card-header">Sales by Category</div>
            <div class="card-body">
                <?php if (!$catSales): ?>
                    <div class="text-muted">No sales yet.</div>
                <?php else: ?>
                    <?php $maxCat = max(array_column($catSales, 'total')); ?>
                    <?php foreach ($catSales as $cat): ?>
                        <?php $w = $maxCat > 0 ? round(($cat['total'] / $maxCat) * 100) : 0; ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small">
                                <span><?= e($cat['name']) ?></span>
                                <span><?= e(format_money($cat['total'])) ?></span>
                            </div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar bg-brown" style="width: <?= $w ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow h-100">
            <div class="card-header">Payment Mix</div>
            <div class="card-body">
                <?php
                $pmStmt = $db->query("
                    SELECT payment_method, COUNT(*) AS cnt, SUM(total_amount) AS total
                    FROM transactions
                    WHERE status = 'completed'
                    GROUP BY payment_method
                ");
                $payMethods = $pmStmt->fetchAll();
                ?>
                <?php if (!$payMethods): ?>
                    <div class="text-muted">No sales yet.</div>
                <?php else: ?>
                    <?php $maxPay = max(array_column($payMethods, 'total')); ?>
                    <?php foreach ($payMethods as $pm): ?>
                        <?php $w = $maxPay > 0 ? round(($pm['total'] / $maxPay) * 100) : 0; ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small">
                                <span><?= e(strtoupper($pm['payment_method'])) ?></span>
                                <span><?= e(format_money($pm['total'])) ?></span>
                            </div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar bg-brown" style="width: <?= $w ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>

