<?php
/**
 * Stock Movements — List + Add + Edit + Delete + Convert
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';
$pdo = Database::getInstance();
$errors = [];

// ══════════════════════════════════════════════════════════════
// POST HANDLER
// ══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die(__('error'));
    }

    $action = post('action');

    // ── Hareket Güncelle ──────────────────────────────────────
    if ($action === 'edit_movement') {
        $mid      = (int) post('movement_id');
        $quantity = (int) post('quantity');
        $type     = post('type');
        $ref      = post('reference');
        $note     = post('note');

        if ($mid <= 0) $errors[] = __('error');
        if ($quantity === 0) $errors[] = __('quantity_required');

        if (empty($errors)) {
            // Eski hareketi al
            $old = $pdo->prepare("SELECT * FROM stock_movements WHERE id = :id");
            $old->execute([':id' => $mid]);
            $oldRow = $old->fetch();

            if (!$oldRow) {
                $errors[] = __('error');
            } else {
                $pdo->beginTransaction();
                try {
                    $oldQty = (int)$oldRow['quantity'];
                    $newQty = $quantity;

                    // Ürün stokunu geri al (eski hareketi iptal et)
                    $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - :q WHERE id = :id")
                        ->execute([':q' => $oldQty, ':id' => $oldRow['product_id']]);

                    // Yeni miktarı uygula
                    $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + :q WHERE id = :id")
                        ->execute([':q' => $newQty, ':id' => $oldRow['product_id']]);

                    // Hareketi güncelle
                    $pdo->prepare("UPDATE stock_movements SET quantity=:q, type=:t, reference=:r, note=:n WHERE id=:id")
                        ->execute([':q' => $newQty, ':t' => $type, ':r' => $ref, ':n' => $note, ':id' => $mid]);

                    $pdo->commit();
                    setFlash('success', __('success'));
                    logAction('Stock Movement Edit', "ID:{$mid} qty:{$oldQty}→{$newQty}");
                    redirect('movements.php');
                } catch (Exception $ex) {
                    $pdo->rollBack();
                    $errors[] = 'Error: ' . $ex->getMessage();
                }
            }
        }
    }

    // ── Hareket Sil ───────────────────────────────────────────
    elseif ($action === 'delete_movement') {
        $mid = (int) post('movement_id');

        if ($mid <= 0) {
            $errors[] = __('error');
        } else {
            $row = $pdo->prepare("SELECT * FROM stock_movements WHERE id = :id");
            $row->execute([':id' => $mid]);
            $mov = $row->fetch();

            if (!$mov) {
                $errors[] = __('error');
            } else {
                $pdo->beginTransaction();
                try {
                    // Stok etkisini geri al
                    $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - :q WHERE id = :id")
                        ->execute([':q' => (int)$mov['quantity'], ':id' => $mov['product_id']]);

                    $pdo->prepare("DELETE FROM stock_movements WHERE id = :id")
                        ->execute([':id' => $mid]);

                    $pdo->commit();
                    setFlash('success', __('success'));
                    logAction('Stock Movement Delete', "ID:{$mid}");
                    redirect('movements.php');
                } catch (Exception $ex) {
                    $pdo->rollBack();
                    $errors[] = 'Error: ' . $ex->getMessage();
                }
            }
        }
    }

    // ── Yeni Hareket Ekle (Manuel Giriş/Çıkış) ───────────────
    elseif ($action === 'add_movement') {
        $productId = (int) post('product_id');
        $quantity  = (int) post('quantity');
        $type      = post('type');      // 'in' | 'out' | 'adjust'
        $ref       = post('reference');
        $note      = post('note');

        if ($productId <= 0) $errors[] = __('select_product');
        if ($quantity <= 0)  $errors[] = __('quantity_required');
        if (!in_array($type, ['in','out','adjust'])) $errors[] = __('error');

        if (empty($errors)) {
            $prodRow = $pdo->prepare("SELECT * FROM products WHERE id = :id");
            $prodRow->execute([':id' => $productId]);
            $prod = $prodRow->fetch();

            if (!$prod) {
                $errors[] = __('product_not_found');
            }

        }

        if (empty($errors)) {
            $pdo->beginTransaction();
            try {
                $delta = ($type === 'out') ? -$quantity : $quantity;
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + :q WHERE id = :id")
                    ->execute([':q' => $delta, ':id' => $productId]);

                $pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, reference, note) VALUES (:pid, :t, :q, :r, :n)")
                    ->execute([':pid' => $productId, ':t' => $type, ':q' => $delta, ':r' => $ref, ':n' => $note]);

                $pdo->commit();
                setFlash('success', __('success'));
                logAction('Stock Movement Add', "PID:{$productId} type:{$type} qty:{$delta}");
                redirect('movements.php');
            } catch (Exception $ex) {
                $pdo->rollBack();
                $errors[] = 'Error: ' . $ex->getMessage();
            }
        }
    }

    // ── Dönüşüm: Kaynaktan Hedef'e Çevir ─────────────────────
    elseif ($action === 'convert_movement') {
        $sourceId  = (int) post('source_id');
        $targetId  = (int) post('target_id');
        $sourceQty = (int) post('source_qty');
        $targetQty = (int) post('target_qty');
        $newSalePrice = post('target_sale_price') !== '' ? (float) post('target_sale_price') : null;
        $note      = post('note');

        if ($sourceId <= 0) $errors[] = __('select_product');
        if ($targetId <= 0) $errors[] = __('select_product');
        if ($sourceId === $targetId) $errors[] = __('error');
        if ($sourceQty <= 0) $errors[] = __('quantity_required');
        if ($targetQty <= 0) $errors[] = __('quantity_required');

        if (empty($errors)) {
            $srcRow = $pdo->prepare("SELECT * FROM products WHERE id = :id");
            $srcRow->execute([':id' => $sourceId]);
            $source = $srcRow->fetch();

            $tgtRow = $pdo->prepare("SELECT * FROM products WHERE id = :id");
            $tgtRow->execute([':id' => $targetId]);
            $target = $tgtRow->fetch();

            if (!$source || !$target) {
                $errors[] = __('error');
            }

        }

        if (empty($errors)) {
            $pdo->beginTransaction();
            try {
                // Kaynak stok düş
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - :q WHERE id = :id")
                    ->execute([':q' => $sourceQty, ':id' => $sourceId]);

                // Hedef stok ekle + isteğe bağlı satış fiyatı güncelle
                if ($newSalePrice !== null && $newSalePrice >= 0) {
                    $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + :q, sale_price = :sp WHERE id = :id")
                        ->execute([':q' => $targetQty, ':sp' => $newSalePrice, ':id' => $targetId]);
                } else {
                    $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + :q WHERE id = :id")
                        ->execute([':q' => $targetQty, ':id' => $targetId]);
                }

                $ref = __('movement_convert');

                // Kaynak hareketi
                $pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, reference, note) VALUES (:pid, 'convert', :qty, :ref, :n)")
                    ->execute([':pid' => $sourceId, ':qty' => -$sourceQty, ':ref' => $ref,
                               ':n' => __('target') . ": {$target['name']} (+{$targetQty}) | {$note}"]);

                // Hedef hareketi
                $pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, reference, note) VALUES (:pid, 'convert', :qty, :ref, :n)")
                    ->execute([':pid' => $targetId, ':qty' => $targetQty, ':ref' => $ref,
                               ':n' => __('source') . ": {$source['name']} (-{$sourceQty}) | {$note}"]);

                $pdo->commit();
                setFlash('success', __('success'));
                logAction('Stock Conversion', "{$source['name']} => {$target['name']}");
                redirect('movements.php');
            } catch (Exception $ex) {
                $pdo->rollBack();
                $errors[] = 'Error: ' . $ex->getMessage();
            }
        }
    }
}

// ══════════════════════════════════════════════════════════════
// VERİ ÇEKİMİ
// ══════════════════════════════════════════════════════════════
$productId = get('product_id');
$type      = get('type');
$dateFrom  = get('date_from');
$dateTo    = get('date_to');

$where  = ['1=1'];
$params = [];

if ($productId !== '') {
    $where[]         = 'sm.product_id = :pid';
    $params[':pid']  = (int) $productId;
}
if ($type !== '') {
    $where[]          = 'sm.type = :type';
    $params[':type']  = $type;
}
if ($dateFrom !== '') {
    $where[]         = 'DATE(sm.created_at) >= :df';
    $params[':df']   = $dateFrom;
}
if ($dateTo !== '') {
    $where[]         = 'DATE(sm.created_at) <= :dt';
    $params[':dt']   = $dateTo;
}

$whereStr = implode(' AND ', $where);
$stmt = $pdo->prepare("
    SELECT sm.*, p.name as product_name, p.unit
    FROM stock_movements sm
    JOIN products p ON sm.product_id = p.id
    WHERE {$whereStr}
    ORDER BY sm.created_at DESC
    LIMIT 300
");
$stmt->execute($params);
$movements = $stmt->fetchAll();

$allProducts = $pdo->query("SELECT id, name, stock_quantity, unit, sale_price, currency FROM products WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

$typeLabels = [
    'in'      => [__('movement_in'),      'badge-stock-ok'],
    'out'     => [__('movement_out'),     'badge-stock-out'],
    'adjust'  => [__('movement_adjust'),  'badge-stock-low'],
    'convert' => [__('movement_convert'), 'badge-stock-low'],
];

$pageTitle = __('stock_movements');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ══ FİLTRE PANELİ ══════════════════════════════════════════ -->
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="movements.php" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label-dark"><?= __('product') ?></label>
                <select name="product_id" class="form-select-dark">
                    <option value=""><?= __('all') ?></option>
                    <?php foreach ($allProducts as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $productId == $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label-dark"><?= __('type') ?></label>
                <select name="type" class="form-select-dark">
                    <option value=""><?= __('all') ?></option>
                    <option value="in"      <?= $type === 'in'      ? 'selected' : '' ?>><?= __('movement_in') ?></option>
                    <option value="out"     <?= $type === 'out'     ? 'selected' : '' ?>><?= __('movement_out') ?></option>
                    <option value="adjust"  <?= $type === 'adjust'  ? 'selected' : '' ?>><?= __('movement_adjust') ?></option>
                    <option value="convert" <?= $type === 'convert' ? 'selected' : '' ?>><?= __('movement_convert') ?></option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label-dark"><?= __('date_start') ?></label>
                <input type="date" name="date_from" class="form-control-dark" value="<?= e($dateFrom) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label-dark"><?= __('date_end') ?></label>
                <input type="date" name="date_to" class="form-control-dark" value="<?= e($dateTo) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn-accent">
                    <i class="bi bi-search"></i> <?= __('filter') ?>
                </button>
                <a href="movements.php" class="btn btn-outline-secondary btn-sm align-self-end">
                    <?= __('all') ?>
                </a>
                <!-- Yeni Hareket Ekle butonu -->
                <button type="button" class="btn btn-outline-info btn-sm align-self-end"
                    onclick="openAddModal()">
                    <i class="bi bi-plus-lg"></i> <?= __('add') ?>
                </button>
                <!-- Dönüşüm butonu -->
                <button type="button" class="btn btn-outline-warning btn-sm align-self-end"
                    onclick="openConvertModal()">
                    <i class="bi bi-recycle"></i> <?= __('stock_convert') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══ HAREKET TABLOSU ════════════════════════════════════════ -->
<div class="panel">
    <div class="panel-header">
        <h5>
            <i class="bi bi-arrow-left-right me-2"></i>
            <?= __('stock_movements') ?>
            <span class="badge bg-secondary ms-2"><?= count($movements) ?></span>
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table-dark-custom">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= __('date') ?></th>
                    <th><?= __('product') ?></th>
                    <th><?= __('type') ?></th>
                    <th><?= __('quantity') ?></th>
                    <th><?= __('reference') ?></th>
                    <th><?= __('note') ?></th>
                    <th style="width:90px;text-align:center;"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                            <?= __('no_data') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movements as $m):
                        [$label, $cls] = $typeLabels[$m['type']] ?? [$m['type'], 'badge-stock-low'];
                        $sign = $m['quantity'] >= 0 ? '+' : '';
                    ?>
                        <tr>
                            <td style="color:var(--text-muted);"><?= $m['id'] ?></td>
                            <td style="font-size:13px;"><?= date('d.m.Y H:i', strtotime($m['created_at'])) ?></td>
                            <td><strong><?= e($m['product_name']) ?></strong></td>
                            <td><span class="<?= $cls ?>"><?= $label ?></span></td>
                            <td>
                                <strong style="color:<?= $m['quantity'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                                    <?= $sign . (int)$m['quantity'] ?>
                                </strong>
                                <span class="text-muted" style="font-size:11px;"><?= e($m['unit']) ?></span>
                            </td>
                            <td style="font-size:13px;color:var(--text-muted);"><?= e($m['reference'] ?? '—') ?></td>
                            <td style="font-size:13px;color:var(--text-muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($m['note'] ?? '') ?>">
                                <?= e($m['note'] ?? '—') ?>
                            </td>
                            <td style="text-align:center;">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn-sm-icon"
                                        title="<?= __('edit') ?>"
                                        onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                            'id'        => $m['id'],
                                            'quantity'  => (int)$m['quantity'],
                                            'type'      => $m['type'],
                                            'reference' => $m['reference'] ?? '',
                                            'note'      => $m['note'] ?? '',
                                        ]), ENT_QUOTES) ?>)">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form method="POST" style="display:inline;"
                                        onsubmit="return confirm('<?= __('confirm_delete') ?>')">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="delete_movement">
                                        <input type="hidden" name="movement_id" value="<?= $m['id'] ?>">
                                        <button type="submit" class="btn-sm-icon btn-delete"
                                            title="<?= __('delete') ?>" style="background:none;border:none;">
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

<!-- ══ MODAL: HAREKET DÜZENLE ════════════════════════════════ -->
<div id="editModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;">
    <div style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;padding:28px;width:100%;max-width:480px;margin:auto;position:relative;">
        <button onclick="closeEditModal()" style="position:absolute;top:14px;right:14px;background:none;border:none;color:var(--text-muted);font-size:20px;cursor:pointer;">&times;</button>
        <h5 style="margin-bottom:20px;"><i class="bi bi-pencil-square me-2"></i><?= __('edit') ?></h5>
        <form method="POST" action="movements.php">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="edit_movement">
            <input type="hidden" name="movement_id" id="editId">

            <div class="mb-3">
                <label class="form-label-dark"><?= __('type') ?></label>
                <select name="type" id="editType" class="form-select-dark">
                    <option value="in"><?= __('movement_in') ?></option>
                    <option value="out"><?= __('movement_out') ?></option>
                    <option value="adjust"><?= __('movement_adjust') ?></option>
                    <option value="convert"><?= __('movement_convert') ?></option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label-dark"><?= __('quantity') ?> <small style="color:var(--text-muted);font-size:11px;">(+giriş / -çıkış)</small></label>
                <input type="number" name="quantity" id="editQty" class="form-control-dark" required style="font-size:18px;padding:12px;">
            </div>
            <div class="mb-3">
                <label class="form-label-dark"><?= __('reference') ?></label>
                <input type="text" name="reference" id="editRef" class="form-control-dark">
            </div>
            <div class="mb-4">
                <label class="form-label-dark"><?= __('note') ?></label>
                <input type="text" name="note" id="editNote" class="form-control-dark">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn-accent flex-fill" style="padding:12px;">
                    <i class="bi bi-check-lg me-1"></i><?= __('save') ?>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="closeEditModal()">
                    <?= __('cancel') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══ MODAL: YENİ HAREKET EKLE ═════════════════════════════ -->
<div id="addModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;">
    <div style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;padding:28px;width:100%;max-width:520px;margin:auto;position:relative;">
        <button onclick="closeAddModal()" style="position:absolute;top:14px;right:14px;background:none;border:none;color:var(--text-muted);font-size:20px;cursor:pointer;">&times;</button>
        <h5 style="margin-bottom:20px;"><i class="bi bi-plus-circle me-2"></i><?= __('add') ?></h5>
        <form method="POST" action="movements.php">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="add_movement">

            <div class="mb-3">
                <label class="form-label-dark"><?= __('product') ?> <span style="color:#ef9a9a">*</span></label>
                <input type="text" id="addSearchInput" class="form-control-dark"
                    placeholder="<?= __('search_product') ?>" autocomplete="off">
                <div id="addDropdown" style="display:none;position:relative;z-index:100;background:var(--card-bg);
                    border:1px solid var(--card-border);border-radius:8px;margin-top:4px;max-height:200px;overflow-y:auto;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);"></div>
                <input type="hidden" name="product_id" id="addProductId">
                <div id="addProductInfo" style="display:none;margin-top:8px;padding:10px;background:rgba(14,165,233,0.07);border-radius:8px;font-size:13px;">
                    <strong id="addProdName"></strong>
                    <span style="color:var(--text-muted);"> — <?= __('stock') ?>: </span>
                    <strong id="addProdStock" style="color:var(--accent);"></strong>
                    <span id="addProdUnit" style="color:var(--text-muted);font-size:11px;"></span>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label-dark"><?= __('type') ?></label>
                    <select name="type" id="addType" class="form-select-dark">
                        <option value="in"><?= __('movement_in') ?></option>
                        <option value="out"><?= __('movement_out') ?></option>
                        <option value="adjust"><?= __('movement_adjust') ?></option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label-dark"><?= __('quantity') ?> <span style="color:#ef9a9a">*</span></label>
                    <input type="number" name="quantity" class="form-control-dark" min="1" required
                        placeholder="0" style="font-size:16px;padding:10px;">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label-dark"><?= __('reference') ?></label>
                <input type="text" name="reference" class="form-control-dark" placeholder="FTR-0001">
            </div>
            <div class="mb-4">
                <label class="form-label-dark"><?= __('note') ?></label>
                <input type="text" name="note" class="form-control-dark" placeholder="<?= __('note') ?>...">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn-accent flex-fill" style="padding:12px;">
                    <i class="bi bi-plus-lg me-1"></i><?= __('perform_stock_entry') ?>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="closeAddModal()">
                    <?= __('cancel') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══ MODAL: DÖNÜŞÜM ════════════════════════════════════════ -->
<div id="convertModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;overflow-y:auto;">
    <div style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;padding:28px;width:100%;max-width:600px;margin:40px auto;position:relative;">
        <button onclick="closeConvertModal()" style="position:absolute;top:14px;right:14px;background:none;border:none;color:var(--text-muted);font-size:20px;cursor:pointer;">&times;</button>
        <h5 style="margin-bottom:6px;"><i class="bi bi-recycle me-2"></i><?= __('stock_convert') ?></h5>
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">
            <i class="bi bi-info-circle me-1 text-info"></i>
            <?= __('conversion_example') ?>
        </div>

        <form method="POST" action="movements.php">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="convert_movement">

            <!-- KAYNAK -->
            <div style="background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);border-radius:12px;padding:16px;margin-bottom:16px;">
                <div style="font-size:12px;font-weight:600;color:var(--danger);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">
                    <i class="bi bi-arrow-up-right me-1"></i><?= __('source') ?>
                </div>
                <div class="row g-3">
                    <div class="col-8">
                        <label class="form-label-dark"><?= __('product') ?></label>
                        <select name="source_id" id="srcSelect" class="form-select-dark" required onchange="updateSrcInfo()">
                            <option value=""><?= __('select_product') ?></option>
                            <?php foreach ($allProducts as $p): ?>
                                <option value="<?= $p['id'] ?>"
                                    data-stock="<?= $p['stock_quantity'] ?>"
                                    data-unit="<?= e($p['unit']) ?>"
                                    data-name="<?= e($p['name']) ?>">
                                    <?= e($p['name']) ?> (<?= __('stock') ?>: <?= $p['stock_quantity'] ?> <?= e($p['unit']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="srcInfo" style="font-size:12px;color:var(--text-muted);margin-top:4px;"></div>
                    </div>
                    <div class="col-4">
                        <label class="form-label-dark"><?= __('quantity') ?></label>
                        <input type="number" name="source_qty" class="form-control-dark" min="1" required placeholder="1">
                    </div>
                </div>
            </div>

            <!-- HEDEF -->
            <div style="background:rgba(34,197,94,0.06);border:1px solid rgba(34,197,94,0.2);border-radius:12px;padding:16px;margin-bottom:16px;">
                <div style="font-size:12px;font-weight:600;color:var(--success);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">
                    <i class="bi bi-arrow-down-left me-1"></i><?= __('target') ?>
                </div>
                <div class="row g-3">
                    <div class="col-8">
                        <label class="form-label-dark"><?= __('product') ?></label>
                        <select name="target_id" id="tgtSelect" class="form-select-dark" required onchange="updateTgtInfo()">
                            <option value=""><?= __('select_product') ?></option>
                            <?php foreach ($allProducts as $p): ?>
                                <option value="<?= $p['id'] ?>"
                                    data-stock="<?= $p['stock_quantity'] ?>"
                                    data-unit="<?= e($p['unit']) ?>"
                                    data-sale="<?= $p['sale_price'] ?>"
                                    data-cur="<?= e($p['currency'] ?? 'USD') ?>">
                                    <?= e($p['name']) ?> (<?= __('stock') ?>: <?= $p['stock_quantity'] ?> <?= e($p['unit']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="tgtInfo" style="font-size:12px;color:var(--text-muted);margin-top:4px;"></div>
                    </div>
                    <div class="col-4">
                        <label class="form-label-dark"><?= __('quantity') ?></label>
                        <input type="number" name="target_qty" class="form-control-dark" min="1" required placeholder="20">
                    </div>
                </div>
                <!-- Satış fiyatı belirleme -->
                <div class="mt-3">
                    <label class="form-label-dark" style="font-size:13px;">
                        <i class="bi bi-tag me-1"></i>
                        <?= __('sale_price') ?> <span style="color:var(--text-muted);font-weight:normal;">(<?= __('txt_optional') ?>)</span>
                    </label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="number" name="target_sale_price" id="tgtSalePrice" class="form-control-dark"
                            step="0.01" min="0" placeholder="0.00" style="max-width:160px;">
                        <span id="tgtCur" style="color:var(--text-muted);font-size:13px;"></span>
                    </div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
                        <?= __('update_purchase_price') ?>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label-dark"><?= __('note') ?></label>
                <input type="text" name="note" class="form-control-dark" placeholder="<?= __('note') ?>...">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn-accent flex-fill" style="padding:12px;">
                    <i class="bi bi-recycle me-1"></i><?= __('perform_conversion') ?>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="closeConvertModal()">
                    <?= __('cancel') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══ JS ═════════════════════════════════════════════════════ -->
<script>
const allProductsData = <?= json_encode(array_map(fn($p) => [
    'id'            => (int)$p['id'],
    'name'          => $p['name'],
    'stock_quantity'=> (int)$p['stock_quantity'],
    'unit'          => $p['unit'],
    'sale_price'    => (float)$p['sale_price'],
    'currency'      => $p['currency'] ?? 'USD',
], $allProducts)) ?>;

// ── Düzenle Modal ─────────────────────────────────────────────
function openEditModal(row) {
    document.getElementById('editId').value   = row.id;
    document.getElementById('editQty').value  = row.quantity;
    document.getElementById('editRef').value  = row.reference;
    document.getElementById('editNote').value = row.note;
    document.getElementById('editType').value = row.type;
    document.getElementById('editModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = '';
}

// ── Ekle Modal ────────────────────────────────────────────────
function openAddModal() {
    document.getElementById('addModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('addSearchInput').focus(), 100);
}
function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Ürün arama (Ekle modal)
const addSearch  = document.getElementById('addSearchInput');
const addDrop    = document.getElementById('addDropdown');

addSearch.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    if (q.length < 1) { addDrop.style.display = 'none'; return; }
    const filtered = allProductsData.filter(p =>
        (p.name + '').toLowerCase().includes(q)
    ).slice(0, 15);

    if (!filtered.length) {
        addDrop.innerHTML = `<div style="padding:12px;color:var(--text-muted);font-size:13px;"><?= __('no_data') ?></div>`;
    } else {
        addDrop.innerHTML = filtered.map(p => `
            <div onclick="selectAddProduct(${p.id})"
                 style="padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid rgba(255,255,255,0.04);display:flex;justify-content:space-between;align-items:center;"
                 onmouseover="this.style.background='rgba(14,165,233,0.1)'" onmouseout="this.style.background=''">
                <strong>${p.name}</strong>
                <span style="color:${p.stock_quantity<=0?'var(--danger)':'var(--accent)'};font-size:12px;">${p.stock_quantity} ${p.unit}</span>
            </div>`).join('');
    }
    addDrop.style.display = 'block';
});

document.addEventListener('click', e => {
    if (!e.target.closest('#addSearchInput') && !e.target.closest('#addDropdown'))
        addDrop.style.display = 'none';
});

function selectAddProduct(id) {
    const p = allProductsData.find(x => x.id === id);
    if (!p) return;
    document.getElementById('addProductId').value = p.id;
    document.getElementById('addProdName').textContent  = p.name;
    document.getElementById('addProdStock').textContent = p.stock_quantity;
    document.getElementById('addProdUnit').textContent  = ' ' + p.unit;
    document.getElementById('addProductInfo').style.display = 'block';
    addSearch.value = p.name;
    addDrop.style.display = 'none';
}

// ── Dönüşüm Modal ─────────────────────────────────────────────
function openConvertModal() {
    document.getElementById('convertModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeConvertModal() {
    document.getElementById('convertModal').style.display = 'none';
    document.body.style.overflow = '';
}

function updateSrcInfo() {
    const sel = document.getElementById('srcSelect');
    const opt = sel.options[sel.selectedIndex];
    const info = document.getElementById('srcInfo');
    if (!sel.value) { info.innerHTML = ''; return; }
    const stock = opt.getAttribute('data-stock');
    const unit  = opt.getAttribute('data-unit');
    info.innerHTML = `<?= __('stock') ?>: <strong style="color:var(--danger)">${stock} ${unit}</strong>`;
}

function updateTgtInfo() {
    const sel  = document.getElementById('tgtSelect');
    const opt  = sel.options[sel.selectedIndex];
    const info = document.getElementById('tgtInfo');
    const cur  = document.getElementById('tgtCur');
    const sale = document.getElementById('tgtSalePrice');
    if (!sel.value) { info.innerHTML = ''; cur.textContent = ''; return; }
    const stock    = opt.getAttribute('data-stock');
    const unit     = opt.getAttribute('data-unit');
    const saleVal  = opt.getAttribute('data-sale');
    const currency = opt.getAttribute('data-cur');
    info.innerHTML = `<?= __('stock') ?>: <strong style="color:var(--success)">${stock} ${unit}</strong>`;
    cur.textContent = currency;
    sale.placeholder = parseFloat(saleVal).toFixed(2);
}

// Backdrop tıklama ile kapat
['editModal','addModal','convertModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            document.body.style.overflow = '';
        }
    });
});
</script>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>