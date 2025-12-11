<?php
require_once __DIR__ . '/../helpers.php';
require_login_json(['admin','manager']);

$db = db();
$roles = ['admin','manager','cashier','barista'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_json(['error' => 'Invalid method'], 405);
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'cashier';
    if (!$name || !$email || !$password) {
        respond_json(['error' => 'All fields are required'], 400);
    }
    if (!in_array($role, $roles, true)) {
        respond_json(['error' => 'Invalid role'], 400);
    }
    $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:n, :e, :p, :r)');
    $stmt->execute([
        'n' => $name,
        'e' => $email,
        'p' => $password,
        'r' => $role,
    ]);
    respond_json(['redirect' => 'users.html', 'message' => 'User created']);
}

if ($action === 'reset') {
    $id = (int) $_POST['user_id'];
    $password = $_POST['password'] ?? '';
    if (!$password) {
        respond_json(['error' => 'Password required'], 400);
    }
    $stmt = $db->prepare('UPDATE users SET password_hash = :p WHERE id = :id');
    $stmt->execute(['p' => $password, 'id' => $id]);
    respond_json(['redirect' => 'users.html', 'message' => 'Password reset']);
}

if ($action === 'changerole') {
    $id = (int) $_POST['user_id'];
    $role = $_POST['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        respond_json(['error' => 'Invalid role'], 400);
    }
    $stmt = $db->prepare('UPDATE users SET role = :r WHERE id = :id');
    $stmt->execute(['r' => $role, 'id' => $id]);
    respond_json(['redirect' => 'users.html', 'message' => 'Role updated']);
}

respond_json(['error' => 'Unknown action'], 400);

