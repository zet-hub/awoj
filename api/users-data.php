<?php
require_once __DIR__ . '/../helpers.php';
require_login_json(['admin','manager']);

$db = db();
$roles = ['admin','manager','cashier','barista'];
$users = $db->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();

respond_json([
    'users' => $users,
    'roles' => $roles,
    'user' => current_user(),
]);

