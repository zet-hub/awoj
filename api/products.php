<?php
require_once __DIR__ . '/../helpers.php';
require_login_json(['admin','manager']);

$db = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_json(['error' => 'Invalid method'], 405);
}

if (isset($_POST['create_category'])) {
    $name = trim($_POST['category_name'] ?? '');
    if ($name) {
        $stmt = $db->prepare('INSERT INTO categories (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);
        respond_json(['redirect' => 'products.html', 'message' => 'Category added']);
    }
    respond_json(['error' => 'Category name required'], 400);
}

if (isset($_POST['item_action'])) {
    $action = $_POST['item_action'];
    $id = (int) ($_POST['item_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $imageUrl = trim($_POST['image_url'] ?? '');

    if ($action === 'create') {
        $stmt = $db->prepare('INSERT INTO menu_items (name, price, category_id, image_url) VALUES (:name, :price, :cid, :img)');
        $stmt->execute([
            'name' => $name,
            'price' => $price,
            'cid' => $categoryId,
            'img' => $imageUrl ?: null,
        ]);
        respond_json(['redirect' => 'products.html', 'message' => 'Menu item created']);
    } elseif ($action === 'update') {
        $stmt = $db->prepare('UPDATE menu_items SET name = :name, price = :price, category_id = :cid, image_url = :img WHERE id = :id');
        $stmt->execute([
            'name' => $name,
            'price' => $price,
            'cid' => $categoryId,
            'img' => $imageUrl ?: null,
            'id' => $id,
        ]);
        respond_json(['redirect' => 'products.html', 'message' => 'Menu item updated']);
    } elseif ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM menu_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        respond_json(['redirect' => 'products.html', 'message' => 'Menu item deleted']);
    }
}

respond_json(['error' => 'Unknown action'], 400);

