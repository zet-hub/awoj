<?php
require_once __DIR__ . '/../helpers.php';
require_login_json();
start_session();
$db = db();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_json(['error' => 'Invalid method'], 405);
}

$action = $_POST['action'] ?? '';

if ($action === 'add_item') {
    $itemId = (int) $_POST['item_id'];
    $qty = max(1, (int) ($_POST['quantity'] ?? 1));
    $_SESSION['cart'][$itemId] = ($_SESSION['cart'][$itemId] ?? 0) + $qty;
    respond_json(['redirect' => 'orders.html', 'message' => 'Item added']);
}

if ($action === 'remove_item') {
    $itemId = (int) $_POST['item_id'];
    unset($_SESSION['cart'][$itemId]);
    respond_json(['redirect' => 'orders.html', 'message' => 'Item removed']);
}

if ($action === 'checkout') {
    $cash = (float) ($_POST['cash'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $discount = max(0, (float) ($_POST['discount'] ?? 0));

    // recalc
    $cart = $_SESSION['cart'];
    $total = 0;
    foreach ($cart as $id => $qty) {
        $stmt = $db->prepare('SELECT price FROM menu_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $price = (float) $stmt->fetchColumn();
        $total += $price * $qty;
    }
    if (!$cart) {
        respond_json(['error' => 'Cart is empty'], 400);
    }
    $gross = $total;
    $total = max(0, $gross - $discount);
    if ($cash < $total) {
        respond_json(['error' => 'Cash provided is less than total'], 400);
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
        foreach ($cart as $id => $qty) {
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
        respond_json(['redirect' => 'orders.html?receipt=' . $transactionId, 'message' => 'Order completed']);
    } catch (Exception $e) {
        $db->rollBack();
        respond_json(['error' => 'Checkout failed: ' . $e->getMessage()], 500);
    }
}

if ($action === 'void_transaction') {
    if (current_user()['role'] !== 'admin') {
        respond_json(['error' => 'Only admin can void'], 403);
    }
    $tid = (int) $_POST['transaction_id'];
    $void = $db->prepare('UPDATE transactions SET status = :status WHERE id = :id');
    $void->execute(['status' => 'voided', 'id' => $tid]);
    $vlog = $db->prepare('INSERT INTO void_logs (transaction_id, acted_by, action, reason) VALUES (:tid, :uid, :action, :reason)');
    $vlog->execute([
        'tid' => $tid,
        'uid' => current_user()['id'],
        'action' => 'voided',
        'reason' => $_POST['reason'] ?? null,
    ]);
    respond_json(['redirect' => 'orders.html', 'message' => 'Transaction voided']);
}

respond_json(['error' => 'Unknown action'], 400);

