<?php
/**
 * Hızlı Stok Güncelleme
 * 
 * Ürünün stok miktarını manuel olarak günceller ve
 * stock_movements tablosuna 'adjust' kaydı düşer.
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$id = (int) get('id');

if ($id <= 0) {
    setFlash('error', 'Invalid product.');
    redirect(BASE_URL . '/modules/products/index.php');
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    redirect(BASE_URL . '/modules/products/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die('CSRF error.');
    }

    $newQty = (int) post('stock_quantity');
    $note = post('note', 'Manual stock update (adjustment)');

    if ($newQty < 0)
        $newQty = 0;

    $oldQty = (int) $product['stock_quantity'];
    $diff = $newQty - $oldQty;

    if ($diff !== 0) {
        $pdo->prepare("UPDATE products SET stock_quantity = :sq WHERE id = :id")->execute([':sq' => $newQty, ':id' => $id]);

        $pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, note) VALUES (:pid, 'adjust', :qty, :note)")
            ->execute([
                ':pid' => $id,
                ':qty' => $diff,
                ':note' => $note
            ]);

        setFlash('success', 'Stock updated successfully.');
    }

    redirect(BASE_URL . '/modules/products/index.php');
}

$pageTitle = __('update_purchase_price');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<div class="panel" style="max-width:500px;">
    <div class="panel-header">
        <h5><i class="bi bi-arrow-repeat me-2"></i>
            <?= __('update_purchase_price') ?>
        </h5>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <?= __('back') ?>
        </a>
    </div>
    <div class="panel-body">

        <div class="mb-4 p-3" style="background:rgba(255,255,255,0.04);border-radius:10px;">
            <div class="mb-1" style="font-size:18px;font-weight:700;">
                <?= e($product['name']) ?>
            </div>
            <div class="text-muted" style="font-size:13px;">
                <?= e($product['dosage_form'] ?? '') ?>
                <?php if ($product['barcode']): ?> |
                    <?= __('barcode') ?>:
                    <?= e($product['barcode']) ?>
                <?php endif; ?>
            </div>
            <div class="mt-2">
                <?= __('stock_qty') ?>: <strong style="font-size:20px;color:var(--accent);">
                    <?= e($product['unit']) ?>
                    </span>
            </div>
        </div>

        <form method="POST" action="stock_update.php?id=<?= $id ?>" data-once>
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <div class="mb-3">
                <label class="form-label-dark">
                    <?= __('stock_qty') ?>
                </label>
                <input type="number" name="stock_quantity" class="form-control-dark"
                    value="<?= (int) $product['stock_quantity'] ?>" min="0" data-positive required>
                <div class="mt-1" style="font-size:12px;color:var(--text-muted);">
                    <?= __('critical_level') ?>:
                    <?= (int) $product['critical_stock'] ?>
                    <?= e($product['unit']) ?>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label-dark">
                    <?= __('note') ?> (Optional)
                </label>
                <input type="text" name="note" class="form-control-dark" placeholder="<?= __('note') ?>">
            </div>

            <button type="submit" class="btn-accent">
                <i class="bi bi-check-lg me-1"></i>
                <?= __('update') ?>
            </button>
        </form>

    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>