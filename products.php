<?php
require_once __DIR__ . '/helpers.php';
require_login(['admin','manager']);

$db = db();

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = trim($_POST['category_name'] ?? '');
    if ($name) {
        $stmt = $db->prepare('INSERT INTO categories (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);
        add_flash('success', 'Category added.');
    } else {
        add_flash('danger', 'Category name required.');
    }
    header('Location: products.php');
    exit;
}

// Handle item create/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_action'])) {
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
        add_flash('success', 'Menu item created.');
    } elseif ($action === 'update') {
        $stmt = $db->prepare('UPDATE menu_items SET name = :name, price = :price, category_id = :cid, image_url = :img WHERE id = :id');
        $stmt->execute([
            'name' => $name,
            'price' => $price,
            'cid' => $categoryId,
            'img' => $imageUrl ?: null,
            'id' => $id,
        ]);
        add_flash('success', 'Menu item updated.');
    } elseif ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM menu_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        add_flash('info', 'Menu item deleted.');
    }

    header('Location: products.php');
    exit;
}

$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$itemsStmt = $db->query('SELECT mi.id, mi.name, mi.price, mi.image_url, c.name AS category_name, mi.category_id FROM menu_items mi JOIN categories c ON c.id = mi.category_id ORDER BY c.name, mi.name');
$items = $itemsStmt->fetchAll();
?>
<?php include __DIR__ . '/partials/header.php'; ?>
<div class="brand-title fs-2 mb-3">Tavern Inventory</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-header">Add Category</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Category name</label>
                        <input class="form-control" name="category_name" required>
                    </div>
                    <button class="btn btn-brown" type="submit" name="create_category">Save</button>
                </form>
            </div>
        </div>

        <div class="card shadow mt-3">
            <div class="card-header">Add Menu Item</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="item_action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Choose...</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image URL (optional)</label>
                        <input class="form-control" name="image_url">
                    </div>
                    <button class="btn btn-brown" type="submit">Create</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Menu Items</span>
                <span class="badge bg-light text-dark pill"><?= count($items) ?> items</span>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th class="text-end">Price</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$items): ?>
                            <tr><td colspan="4" class="text-center p-4 text-muted">No items yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="align-middle">
                                        <div class="fw-semibold"><?= e($item['name']) ?></div>
                                        <?php if ($item['image_url']): ?>
                                            <div class="small text-muted"><?= e($item['image_url']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle"><?= e($item['category_name']) ?></td>
                                    <td class="align-middle text-end"><?= e(format_money($item['price'])) ?></td>
                                    <td class="align-middle text-end">
                                        <form class="d-inline" method="post">
                                            <input type="hidden" name="item_action" value="delete">
                                            <input type="hidden" name="item_id" value="<?= e($item['id']) ?>">
                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete item?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td colspan="4">
                                        <form class="row g-2 align-items-center" method="post">
                                            <input type="hidden" name="item_action" value="update">
                                            <input type="hidden" name="item_id" value="<?= e($item['id']) ?>">
                                            <div class="col-md-4">
                                                <input class="form-control form-control-sm" name="name" value="<?= e($item['name']) ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="price" value="<?= e($item['price']) ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <select class="form-select form-select-sm" name="category_id">
                                                    <?php foreach ($categories as $c): ?>
                                                        <option value="<?= e($c['id']) ?>" <?= $c['id'] == $item['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control form-control-sm" name="image_url" placeholder="Image URL" value="<?= e($item['image_url']) ?>">
                                            </div>
                                            <div class="col-md-1 text-end">
                                                <button class="btn btn-sm btn-brown" type="submit">Save</button>
                                            </div>
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

