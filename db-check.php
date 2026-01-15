<?php
require 'helpers.php';
$pdo = db();
$stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE email = :email");
$stmt->execute(['email' => 'admin@example.com']);
$user = $stmt->fetch();
var_dump($user);
