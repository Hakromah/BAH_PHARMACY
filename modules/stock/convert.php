<?php
/**
 * Stock Conversion
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();

$errors = [];

// ── POST: Dönüşümü İşle ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die(__('error'));
    }

    $sourceId = (int) post('source_id');
    $targetId = (int) post('target_id');
    $sourceQty = (int) post('source_qty'); // Kaç kutu
    $targetQty = (int) post('target_qty'); // Karşılık kaç adet
    $note = post('note');

    // Validasyon
    if ($sourceId <= 0)
        $errors[] = __('select_product');
    if ($targetId <= 0)
        $errors[] = __('select_product');
    if ($sourceId === $targetId)
        $errors[] = __('error');
    if ($sourceQty <= 0)
        $errors[] = __('quantity_required');
    if ($targetQty <= 0)
        $errors[] = __('quantity_required');

    if (empty($errors)) {
        $src = $pdo->prepare("SELECT * FROM products WHERE id = :id FOR UPDATE");
        $src->execute([':id' => $sourceId]);
        $source = $src->fetch();

        $tgt = $pdo->prepare("SELECT * FROM products WHERE id = :id FOR UPDATE");
        $tgt->execute([':id' => $targetId]);
        $target = $tgt->fetch();

        if (!$source || !$target) {
            $errors[] = __('error');
        } elseif ($source['stock_quantity'] < $sourceQty) {
            $errors[] = __('stock_low') . ": {$source['stock_quantity']} {$source['unit']}";
        }
    }

    if (empty($errors)) {
        // Transaction ile gerçekleştir
        $pdo->beginTransaction();
        try {
            // Kaynak stok düş (-)
            $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - :q WHERE id = :id")
                ->execute([':q' => $sourceQty, ':id' => $sourceId]);

            // Hedef stok ekle (+)
            $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + :q WHERE id = :id")
                ->execute([':q' => $targetQty, ':id' => $targetId]);

            $ref = __('movement_convert');
            // Kaynak hareket
            $pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, reference, note) 
                           VALUES (:pid, 'convert', :qty, :ref, :n)")
                ->execute([':pid' => $sourceId, ':qty' => -$sourceQty, ':ref' => $ref, ':n' => __('target') . ": {$target['name']} (+{$targetQty})"]);

            // Hedef hareket
            $pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, reference, note) 
                           VALUES (:pid, 'convert', :qty, :ref, :n)")
                ->execute([':pid' => $targetId, ':qty' => $targetQty, ':ref' => $ref, ':n' => __('source') . ": {$source['name']} (-{$sourceQty})"]);

            $pdo->commit();
            logAction('Stock Conversion', "{$source['name']} => {$target['name']}");
            setFlash('success', __('success'));
            redirect('convert.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

$products = $pdo->query("SELECT id, name, stock_quantity, unit FROM products WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$pageTitle = __('stock_convert');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li>
                    <?= e($err) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">

    <!-- Form -->
    <div class="col-md-6">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-recycle me-2"></i>
                    <?= __('new_conversion') ?>
                </h5>
            </div>
            <div class="panel-body">

                <div class="mb-3 p-3"
                    style="background:rgba(14,165,233,0.08);border:1px solid rgba(14,165,233,0.2);border-radius:10px;font-size:13px;color:rgba(255,255,255,0.7);">
                    <i class="bi bi-info-circle me-2 text-info"></i>
                    <strong>
                        <?= __('example') ?>:
                    </strong>
                    <?= __('conversion_example') ?>
                </div>

                <form method="POST" action="convert.php" data-once>
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('conversion_source_help') ?>
                        </label>
                        <select name="source_id" id="source_id" class="form-select-dark" required
                            onchange="updateSourceStock()">
                            <option value="">
                                <?= __('select_product') ?>
                            </option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>" data-stock="<?= $p['stock_quantity'] ?>"
                                    data-unit="<?= e($p['unit']) ?>">
                                    <?= e($p['name']) ?> (<?= __('stock') ?>:
                                    <?= $p['stock_quantity'] ?>
                                    <?= e($p['unit']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="source_info" class="mt-1" style="font-size:12px;color:var(--text-muted);"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('conversion_source_qty') ?>
                        </label>
                        <input type="number" name="source_qty" class="form-control-dark" min="1" required
                            placeholder="<?= __('example') ?> 1">
                    </div>

                    <hr style=" border-color:rgba(255,255,255,0.08);">

                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('conversion_target_help') ?>
                        </label>
                        <select name="target_id" class="form-select-dark" required>
                            <option value="">
                                <?= __('select_product') ?>
                            </option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= e($p['name']) ?>
                                    (<?= __('current') ?>:
                                    <?= $p['stock_quantity'] ?>
                                    <?= e($p['unit']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('conversion_target_qty') ?>
                        </label>
                        <input type="number" name="target_qty" class="form-control-dark" min="1" required
                            placeholder="<?= __('example') ?> 5">
                    </div>


                    <div class=" mb-4">
                        <label class="form-label-dark">
                            <?= __('note') ?>
                        </label>
                        <input type="text" name="note" class="form-control-dark" placeholder="<?= __('note') ?>...">
                    </div>

                    <button type="submit" class="btn-accent">
                        <i class="bi bi-recycle me-1"></i>
                        <?= __('perform_conversion') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Son Dönüşümler -->
    <div class="col-md-6">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-clock-history me-2"></i>
                    <?= __('recent_conversions') ?>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table-dark-custom">
                    <thead>
                        <tr>
                            <th>
                                <?= __('date') ?>
                            </th>
                            <th>
                                <?= __('product') ?>
                            </th>
                            <th>
                                <?= __('quantity') ?>
                            </th>
                            <th>
                                <?= __('note') ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent = $pdo->query("
                            SELECT sm.*, p.name as pname, p.unit
                            FROM stock_movements sm
                            JOIN products p ON sm.product_id = p.id
                            WHERE sm.type = 'convert'
                            ORDER BY sm.created_at DESC
                            LIMIT 15
                        ")->fetchAll();
                        if (empty($recent)):
                            ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <?= __('no_data') ?>
                                </td>
                            </tr>
                        <?php else:
                            foreach ($recent as $r):
                                ?>
                                <tr>
                                    <td style="font-size:12px;">
                                        <?= date('d.m.Y H:i', strtotime($r['created_at'])) ?>
                                    </td>
                                    <td>
                                        <?= e($r['pname']) ?>
                                    </td>
                                    <td>
                                        <strong style="color:<?= $r['quantity'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                                            <?= ($r['quantity'] > 0 ? '+' : '') . (int) $r['quantity'] ?>
                                        </strong>
                                        <span class="text-muted" style="font-size:11px;">
                                            <?= e($r['unit']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:12px;color:var(--text-muted);">
                                        <?= e($r['note'] ?? '—') ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    function updateSourceStock() {
        const sel = document.getElementById('source_id');
        const info = document.getElementById('source_info');
        if (!sel.value) {
            info.innerHTML = '';
            return;
        }
        const opt = sel.options[sel.selectedIndex];
        const stock = opt.getAttribute('data-stock');
        const unit = opt.getAttribute('data-unit');
        info.innerHTML = `<?= __('stock') ?>: <strong style="color:var(--accent)">${stock} ${unit}</strong>`;
    }
</script>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>