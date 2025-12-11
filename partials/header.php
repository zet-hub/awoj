<?php
require_once __DIR__ . '/../helpers.php';
start_session();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body class="bg parchment">
<nav class="navbar navbar-expand-lg navbar-dark bg-brown shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php"><?= e(APP_NAME) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($user): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                    <?php if (in_array($user['role'], ['admin','manager'], true)): ?>
                        <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                        <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                        <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="staff_activity.php">Staff</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <?php if ($user): ?>
                    <span class="text-light small">Logged in as <?= e($user['name']) ?> (<?= e($user['role']) ?>)</span>
                    <a class="btn btn-sm btn-outline-light" href="logout.php">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<main class="container py-4">
    <?php foreach (get_flashes() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> shadow-sm"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>

