<?php
require_once __DIR__ . '/helpers.php';
require_login();

$db = db();
$user = current_user();
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';

    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = :id');
    $stmt->execute(['id' => $user['id']]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($current, $hash)) {
        $message = ['type' => 'danger', 'text' => 'Current password incorrect.'];
    } elseif (strlen($new) < 8) {
        $message = ['type' => 'danger', 'text' => 'New password must be at least 8 characters.'];
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $update = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $update->execute(['hash' => $newHash, 'id' => $user['id']]);
        $message = ['type' => 'success', 'text' => 'Password updated.'];
    }
}
?>
<?php include __DIR__ . '/partials/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">Account Settings</div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?= e($message['type']) ?>"><?= e($message['text']) ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Current password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New password</label>
                        <input type="password" class="form-control" name="new_password" required>
                    </div>
                    <button class="btn btn-brown" type="submit">Update password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>

