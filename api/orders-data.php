<?php
require_once __DIR__ . '/../helpers.php';
require_login_json();
start_session();

$db = db();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function recalc_cart(PDO $db): array
{
    $cart = $_SESSION['cart'];
    $items = [];
    $total = 0;
    foreach ($cart as $id => $qty) {
        $stmt = $db->prepare('SELECT name, price FROM menu_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if ($row = $stmt->fetch()) {
            $lineTotal = (float) $row['price'] * $qty;
            $items[] = [
                'id' => (int) $id,
                'name' => $row['name'],
                'price' => (float) $row['price'],
                'qty' => (int) $qty,
                'lineTotal' => $lineTotal,
            ];
            $total += $lineTotal;
        }
    }
    return ['items' => $items, 'total' => $total];
}

$menuItems = fetch_menu_items();
$cartData = recalc_cart($db);
$receipt = null;
$receiptItems = [];

if (isset($_GET['receipt'])) {
    $stmt = $db->prepare('SELECT id, total_amount, cash_received, change_given, discount_amount, payment_method, status, created_at FROM transactions WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['receipt']]);
    $receipt = $stmt->fetch();

    if ($receipt) {
        $itemsStmt = $db->prepare('
            SELECT ti.quantity, ti.price_each, mi.name
            FROM transaction_items ti
            JOIN menu_items mi ON mi.id = ti.menu_item_id
            WHERE ti.transaction_id = :id
        ');
        $itemsStmt->execute(['id' => (int) $_GET['receipt']]);
        $receiptItems = $itemsStmt->fetchAll();
    }
}

$ordersStmt = $db->query("
    SELECT TOP 15 t.id, t.total_amount, t.discount_amount, t.cash_received, t.change_given, t.payment_method, t.status, t.created_at, u.name AS cashier
    FROM transactions t
    JOIN users u ON u.id = t.user_id
    ORDER BY t.created_at DESC
");
$orders = $ordersStmt->fetchAll();

respond_json([
    'menuItems' => $menuItems,
    'cart' => $cartData,
    'receipt' => $receipt,
    'receiptItems' => $receiptItems,
    'orders' => $orders,
    'user' => current_user(),
]);

