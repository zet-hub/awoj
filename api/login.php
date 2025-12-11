<?php
require_once __DIR__ . '/../helpers.php';
start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_json(['error' => 'Invalid method'], 405);
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = db()->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

$isValid = false;
if ($user) {
    if (password_verify($password, $user['password_hash'])) {
        $isValid = true;
    } elseif ($password === $user['password_hash']) { // fallback for plain-text values
        $isValid = true;
    }
}

if ($isValid) {
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
    log_access($user['id'], 'login');
    respond_json(['redirect' => 'dashboard.html', 'message' => 'Welcome back, ' . $user['name'] . '!']);
}

respond_json(['error' => 'Invalid credentials'], 401);

