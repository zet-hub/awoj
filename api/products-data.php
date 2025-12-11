<?php
require_once __DIR__ . '/../helpers.php';
require_login_json(['admin','manager']);

$db = db();
$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$itemsStmt = $db->query('SELECT mi.id, mi.name, mi.price, mi.image_url, c.name AS category_name, mi.category_id FROM menu_items mi JOIN categories c ON c.id = mi.category_id ORDER BY c.name, mi.name');
$items = $itemsStmt->fetchAll();

respond_json([
    'categories' => $categories,
    'items' => $items,
    'user' => current_user(),
]);

