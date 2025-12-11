<?php
require_once __DIR__ . '/helpers.php';
require_login(['admin','manager']);

$db = db();
$roles = ['admin','manager','cashier','barista'];
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'cashier';
        if (!$name || !$email || !$password) {
            add_flash('danger', 'All fields are required.');
        } elseif (!in_array($role, $roles, true)) {
            add_flash('danger', 'Invalid role.');
        } else {
            $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:n, :e, :p, :r)');
            $stmt->execute([
                'n' => $name,
                'e' => $email,
                'p' => $password,
                'r' => $role,
            ]);
            add_flash('success', 'User created.');
        }
    } elseif ($action === 'reset') {
        $id = (int) $_POST['user_id'];
        $password = $_POST['password'] ?? '';
        if (!$password) {
            add_flash('danger', 'Password required.');
        } else {
            $stmt = $db->prepare('UPDATE users SET password_hash = :p WHERE id = :id');
            $stmt->execute(['p' => $password, 'id' => $id]);
            add_flash('success', 'Password reset.');
        }
    } elseif ($action === 'changerole') {
        $id = (int) $_POST['user_id'];
        $role = $_POST['role'] ?? '';
        if (!in_array($role, $roles, true)) {
            add_flash('danger', 'Invalid role.');
        } else {
            $stmt = $db->prepare('UPDATE users SET role = :r WHERE id = :id');
            $stmt->execute(['r' => $role, 'id' => $id]);
            add_flash('success', 'Role updated.');
        }
    }
    header('Location: users.php');
    exit;
}

$users = $db->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
?>
<?php include __DIR__ . '/partials/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="brand-title fs-2">User Management</div>
    <span class="badge bg-light text-dark pill"><?= count($users) ?> users</span>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-header">Add User</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (plain for testing)</label>
                        <input type="text" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= e($r) ?>"><?= e($r) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-brown" type="submit">Create</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header">Users</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$users): ?>
                            <tr><td colspan="5" class="text-center p-4 text-muted">No users.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= e($u['name']) ?></td>
                                    <td><?= e($u['email']) ?></td>
                                    <td><?= e($u['role']) ?></td>
                                    <td><?= e($u['created_at']) ?></td>
                                    <td class="text-end">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="changerole">
                                            <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                                            <select name="role" class="form-select form-select-sm d-inline w-auto">
                                                <?php foreach ($roles as $r): ?>
                                                    <option value="<?= e($r) ?>" <?= $r === $u['role'] ? 'selected' : '' ?>><?= e($r) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-outline-secondary">Update</button>
                                        </form>
                                        <form method="post" class="d-inline ms-2">
                                            <input type="hidden" name="action" value="reset">
                                            <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                                            <input type="text" name="password" class="form-control form-control-sm d-inline w-auto" placeholder="New pass" required>
                                            <button class="btn btn-sm btn-outline-primary">Reset</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>


