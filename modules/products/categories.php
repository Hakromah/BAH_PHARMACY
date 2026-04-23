<?php
/**
 * Category Management
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$action = post('action') ?: get('action');

// ── POST: Kaydet / Sil ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die('CSRF error.');
    }

    if ($action === 'save') {
        $catId = (int) post('cat_id');
        $name = post('name');
        $desc = post('description');

        if (empty($name)) {
            setFlash('error', __('category_name_required'));
        } else {
            if ($catId > 0) {
                // Update
                $pdo->prepare("UPDATE categories SET name = :n, description = :d WHERE id = :id")->execute([':n' => $name, ':d' => $desc, ':id' => $catId]);
                setFlash('success', __('category_updated'));
            } else {
                // Add
                $pdo->prepare("INSERT INTO categories (name, description) VALUES (:n, :d)")->execute([':n' => $name, ':d' => $desc]);
                setFlash('success', __('category_added'));
            }
            redirect(BASE_URL . '/modules/products/categories.php');
        }
    } elseif ($action === 'delete') {
        $catId = (int) post('cat_id');

        // Ürün kontrolü
        $prodCheck = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = :id");
        $prodCheck->execute([':id' => $catId]);
        if ($prodCheck->fetchColumn() > 0) {
            setFlash('error', __('category_delete_error_has_products'));
        } else {
            $pdo->prepare("DELETE FROM categories WHERE id = :id")->execute([':id' => $catId]);
            setFlash('success', __('category_deleted'));
        }
        redirect(BASE_URL . '/modules/products/categories.php');
    }
}

// Düzenleme
$editCat = null;
if (get('edit')) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute([':id' => (int) get('edit')]);
    $editCat = $stmt->fetch();
}

$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count FROM categories c ORDER BY name")->fetchAll();

$pageTitle = __('categories');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<div class="row g-4">

    <!-- Form -->
    <div class="col-md-4">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-<?= $editCat ? 'pencil' : 'plus-circle' ?> me-2"></i>
                    <?= $editCat ? __('edit_category') : __('new_category') ?>
                </h5>
            </div>
            <div class="panel-body">
                <form method="POST" action="categories.php" data-once>
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="cat_id" value="<?= $editCat ? $editCat['id'] : 0 ?>">

                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('category_name') ?><span style="color:#ef9a9a">*</span>
                        </label>
                        <input type="text" name="name" class="form-control-dark" required
                            value="<?= e($editCat['name'] ?? '') ?>" placeholder="<?= __('category_name') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label-dark">
                            <?= __('description') ?>
                        </label>
                        <textarea name="description" class="form-control-dark" rows="3"
                            placeholder="<?= __('description') ?>..."><?= e($editCat['description'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-accent">
                            <i class="bi bi-check-lg me-1"></i>
                            <?= $editCat ? __('update') : __('add') ?>
                        </button>
                        <?php if ($editCat): ?>
                            <a href="categories.php" class="btn btn-outline-secondary btn-sm">
                                <?= __('cancel') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Liste -->
    <div class="col-md-8">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-tags me-2"></i>
                    <?= __('categories') ?><span class="badge bg-secondary ms-2">
                        <?= count($categories) ?>
                    </span>
                </h5>
            </div>
            <table class="table-dark-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>
                            <?= __('category_name') ?>
                        </th>
                        <th>
                            <?= __('description') ?>
                        </th>
                        <th>
                            <?= __('products') ?>
                        </th>
                        <th>
                            <?= __('actions') ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <?= __('no_data') ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $c): ?>
                            <tr>
                                <td>
                                    <?= $c['id'] ?>
                                </td>
                                <td><strong>
                                        <?= e($c['name']) ?>
                                    </strong></td>
                                <td style="color:var(--text-muted);font-size:13px;">
                                    <?= e($c['description'] ?? '—') ?>
                                </td>
                                <td>
                                    <span class="badge-stock-ok">
                                        <?= (int) $c['product_count'] ?>
                                        <?= __('products') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="categories.php?edit=<?= $c['id'] ?>" class="btn-sm-icon btn-edit"
                                            title="<?= __('edit') ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="categories.php" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn-sm-icon btn-delete"
                                                data-confirm="<?= __('confirm_delete') ?>" title="<?= __('delete') ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>