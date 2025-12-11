<?php
require_once __DIR__ . '/../helpers.php';
require_login_json();

$db = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_json(['error' => 'Invalid method'], 405);
}

$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';

$stmt = $db->prepare('SELECT password_hash FROM users WHERE id = :id');
$stmt->execute(['id' => $user['id']]);
$hash = $stmt->fetchColumn();

if (!$hash || !password_verify($current, $hash)) {
    respond_json(['error' => 'Current password incorrect'], 400);
}
if (strlen($new) < 8) {
    respond_json(['error' => 'New password must be at least 8 characters'], 400);
}

$newHash = password_hash($new, PASSWORD_DEFAULT);
$update = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
$update->execute(['hash' => $newHash, 'id' => $user['id']]);

respond_json(['redirect' => 'settings.html', 'message' => 'Password updated']);

