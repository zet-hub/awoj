<?php
require_once __DIR__ . '/helpers.php';
require_login();

start_session();
$db = db();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function recalc_cart(PDO $db): array
{
    $cart = $_SESSION['cart'];
    $total = 0;
    foreach ($cart as $id => $qty) {
        $stmt = $db->prepare('SELECT price FROM menu_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $price = (float) $stmt->fetchColumn();
        $total += $price * $qty;
    }
    return ['items' => $cart, 'total' => $total];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_item'])) {
        $itemId = (int) $_POST['item_id'];
        $qty = max(1, (int) $_POST['quantity']);
        $_SESSION['cart'][$itemId] = ($_SESSION['cart'][$itemId] ?? 0) + $qty;
        add_flash('success', 'Item added to cart.');
        header('Location: orders.php');
        exit;
    }

    if (isset($_POST['remove_item'])) {
        $itemId = (int) $_POST['item_id'];
        unset($_SESSION['cart'][$itemId]);
        add_flash('info', 'Item removed.');
        header('Location: orders.php');
        exit;
    }

    if (isset($_POST['checkout'])) {
        $cash = (float) ($_POST['cash'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $discount = max(0, (float) ($_POST['discount'] ?? 0));
        $cartData = recalc_cart($db);
        if (!$cartData['items']) {
            add_flash('danger', 'Cart is empty.');
            header('Location: orders.php');
            exit;
        }
        $gross = $cartData['total'];
        $total = max(0, $gross - $discount);
        if ($cash < $total) {
            add_flash('danger', 'Cash provided is less than total.');
            header('Location: orders.php');
            exit;
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare('INSERT INTO transactions (user_id, total_amount, discount_amount, cash_received, change_given, payment_method, status) OUTPUT INSERTED.id VALUES (:user_id, :total, :discount, :cash, :change, :pm, :status)');
            $stmt->execute([
                'user_id' => current_user()['id'],
                'total' => $total,
                'discount' => $discount,
                'cash' => $cash,
                'change' => $cash - $total,
                'pm' => $paymentMethod,
                'status' => 'completed',
            ]);
            $transactionId = $stmt->fetchColumn();

            $itemStmt = $db->prepare('INSERT INTO transaction_items (transaction_id, menu_item_id, quantity, price_each) VALUES (:tid, :mid, :qty, :price)');
            foreach ($cartData['items'] as $id => $qty) {
                $priceStmt = $db->prepare('SELECT price FROM menu_items WHERE id = :id');
                $priceStmt->execute(['id' => $id]);
                $price = (float) $priceStmt->fetchColumn();
                $itemStmt->execute([
                    'tid' => $transactionId,
                    'mid' => $id,
                    'qty' => $qty,
                    'price' => $price,
                ]);
            }

            $db->commit();
            $_SESSION['cart'] = [];
            add_flash('success', 'Order completed.');
            header('Location: orders.php?receipt=' . $transactionId);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            add_flash('danger', 'Checkout failed: ' . $e->getMessage());
            header('Location: orders.php');
            exit;
        }
    }

    if (isset($_POST['void_transaction'])) {
        $tid = (int) $_POST['transaction_id'];
        if (current_user()['role'] !== 'admin') {
            add_flash('danger', 'Only admin can void.');
        } else {
            $void = $db->prepare('UPDATE transactions SET status = :status WHERE id = :id');
            $void->execute(['status' => 'voided', 'id' => $tid]);
            $vlog = $db->prepare('INSERT INTO void_logs (transaction_id, acted_by, action, reason) VALUES (:tid, :uid, :action, :reason)');
            $vlog->execute([
                'tid' => $tid,
                'uid' => current_user()['id'],
                'action' => 'voided',
                'reason' => $_POST['reason'] ?? null,
            ]);
            add_flash('info', 'Transaction voided.');
        }
        header('Location: orders.php');
        exit;
    }
}

$menuItems = fetch_menu_items();
$cartData = recalc_cart($db);
$receipt = null;

if (isset($_GET['receipt'])) {
    $stmt = $db->prepare('SELECT id, total_amount, cash_received, change_given, discount_amount, payment_method, status, created_at FROM transactions WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['receipt']]);
    $receipt = $stmt->fetch();

    $itemsStmt = $db->prepare('
        SELECT ti.quantity, ti.price_each, mi.name
        FROM transaction_items ti
        JOIN menu_items mi ON mi.id = ti.menu_item_id
        WHERE ti.transaction_id = :id
    ');
    $itemsStmt->execute(['id' => (int) $_GET['receipt']]);
    $receiptItems = $itemsStmt->fetchAll();
}
?>
<?php include __DIR__ . '/partials/header.php'; ?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow">
            <div class="card-header">Menu</div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($menuItems as $item): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="fw-bold"><?= e($item['name']) ?></div>
                                    <div class="text-muted small"><?= e($item['category']) ?></div>
                                    <div class="fs-5 fw-semibold mt-2"><?= e(format_money($item['price'])) ?></div>
                                    <form method="post" class="mt-3">
                                        <input type="hidden" name="item_id" value="<?= e($item['id']) ?>">
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="quantity" class="form-control" value="1" min="1">
                                            <button class="btn btn-brown" type="submit" name="add_item">Add</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$menuItems): ?>
                        <div class="text-muted">No menu items yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow">
            <div class="card-header">Cart</div>
            <div class="card-body">
                <?php if (!$cartData['items']): ?>
                    <p class="text-muted">Your cart is empty.</p>
                <?php else: ?>
                    <ul class="list-group mb-3">
                        <?php foreach ($cartData['items'] as $id => $qty): ?>
                            <?php
                            $stmt = $db->prepare('SELECT name, price FROM menu_items WHERE id = :id');
                            $stmt->execute(['id' => $id]);
                            $row = $stmt->fetch();
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold"><?= e($row['name']) ?></div>
                                    <div class="text-muted small">Qty: <?= e($qty) ?> &bull; <?= e(format_money($row['price'])) ?></div>
                                </div>
                                <form method="post">
                                    <input type="hidden" name="item_id" value="<?= e($id) ?>">
                                    <button class="btn btn-sm btn-outline-danger" name="remove_item">Remove</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="fw-bold">Total</div>
                        <div class="fs-5 fw-semibold"><?= e(format_money($cartData['total'])) ?></div>
                    </div>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Cash received</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="cash" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment method</label>
                            <select class="form-select" name="payment_method">
                                <option value="cash">Cash</option>
                                <option value="gcash">GCash</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Discount (amount)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="discount" value="0">
                        </div>
                        <div class="d-grid">
                            <button class="btn btn-brown" type="submit" name="checkout">Checkout</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($receipt): ?>
            <div class="card shadow mt-3">
                <div class="card-header">Receipt #<?= e($receipt['id']) ?></div>
                <div class="card-body">
                    <div class="small text-muted"><?= e($receipt['created_at']) ?></div>
                    <ul class="list-group my-3">
                        <?php foreach ($receiptItems as $it): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><?= e($it['name']) ?> x <?= e($it['quantity']) ?></span>
                                <span><?= e(format_money($it['price_each'] * $it['quantity'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="d-flex justify-content-between">
                        <span>Total</span>
                        <span class="fw-semibold"><?= e(format_money($receipt['total_amount'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Discount</span>
                        <span><?= e(format_money($receipt['discount_amount'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Cash</span>
                        <span><?= e(format_money($receipt['cash_received'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Payment</span>
                        <span class="text-uppercase"><?= e($receipt['payment_method']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Change</span>
                        <span><?= e(format_money($receipt['change_given'])) ?></span>
                    </div>
                    <div class="small mt-2">Status: <?= e($receipt['status']) ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Recent orders list
$ordersStmt = $db->query("
    SELECT TOP 15 t.id, t.total_amount, t.discount_amount, t.cash_received, t.change_given, t.payment_method, t.status, t.created_at, u.name AS cashier
    FROM transactions t
    JOIN users u ON u.id = t.user_id
    ORDER BY t.created_at DESC
");
$orders = $ordersStmt->fetchAll();
?>
<div class="card shadow mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Recent Orders</span>
        <span class="badge bg-light text-dark pill"><?= count($orders) ?> shown</span>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Cashier</th>
                    <th>Payment</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Discount</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$orders): ?>
                    <tr><td colspan="8" class="text-center p-4 text-muted">No orders yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?= e($o['id']) ?></td>
                            <td><?= e($o['created_at']) ?></td>
                            <td><?= e($o['cashier']) ?></td>
                            <td class="text-uppercase"><?= e($o['payment_method']) ?></td>
                            <td class="text-end"><?= e(format_money($o['total_amount'])) ?></td>
                            <td class="text-end"><?= e(format_money($o['discount_amount'])) ?></td>
                            <td><?= e($o['status']) ?></td>
                            <td class="text-end">
                                <?php if ($o['status'] === 'completed' && current_user()['role'] === 'admin'): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="transaction_id" value="<?= e($o['id']) ?>">
                                        <input type="text" name="reason" class="form-control form-control-sm d-inline w-auto" placeholder="Reason">
                                        <button class="btn btn-sm btn-outline-danger" name="void_transaction" onclick="return confirm('Void this transaction?')">Void</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>

