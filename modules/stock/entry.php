<?php
/**
 * Stock Entry Module
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$errors = [];

// ── POST: Stok Girişi Yap ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die(__('error'));
    }

    $productId = (int) post('product_id');
    $quantity = (int) post('quantity');
    $newPurchPrice = post('purchase_price') !== '' ? (float) post('purchase_price') : null;
    $reference = post('reference');  // Fatura/İrsaliye No
    $note = post('note');
    $updatePrice = (bool) post('update_price');

    // Validasyon
    if ($productId <= 0)
        $errors[] = __('select_product');
    if ($quantity <= 0)
        $errors[] = __('quantity_required');

    // Ürünü kontrol et
    $product = null;
    if ($productId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();
        if (!$product) {
            $errors[] = __('product_not_found');
        }
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Görsel yükleme kontrolü
            $newImageUrl = $product['image_url'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Yükleme mantığı (basitçe es geçiyoruz, form upload)
            }

            // Fiyat güncellenecekse
            $currentPurchasePrice = $product['purchase_price'];
            if ($updatePrice && $newPurchPrice !== null && $newPurchPrice >= 0) {
                $currentPurchasePrice = $newPurchPrice;
            }

            // Ürünü güncelle
            $stmtUpd = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity + :qty,
                    purchase_price = :pprice,
                    image_url = :img
                WHERE id = :id
            ");
            $stmtUpd->execute([
                ':qty' => $quantity,
                ':pprice' => $currentPurchasePrice,
                ':img' => $newImageUrl,
                ':id' => $productId
            ]);

            // Hareket kaydı
            $stmtMov = $pdo->prepare("
                INSERT INTO stock_movements (product_id, type, quantity, reference, note)
                VALUES (:pid, 'in', :qty, :ref, :note)
            ");
            $stmtMov->execute([
                ':pid' => $productId,
                ':qty' => $quantity,
                ':ref' => $reference,
                ':note' => $note
            ]);

            $pdo->commit();
            setFlash('success', __('success'));
            logAction('Stock Entry', "Product ID:{$productId} | Quantity:{$quantity}");
            redirect('entry.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'An error occurred: ' . $e->getMessage();
        }
    }
}

// ── GET: Form Verileri & Son Girişler ───────────────────
$allProducts = $pdo->query("
    SELECT p.id, p.name, p.barcode, p.dosage_form, 
           p.stock_quantity, p.critical_stock, p.unit,
           p.purchase_price, p.sale_price, p.currency,
           c.name as cat_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY p.name ASC
")->fetchAll();

$recentEntries = $pdo->query("
    SELECT sm.*, p.name as product_name, p.unit
    FROM stock_movements sm
    JOIN products p ON sm.product_id = p.id
    WHERE sm.type = 'in'
    ORDER BY sm.created_at DESC
    LIMIT 10
")->fetchAll();

$pageTitle = __('stock_entry');
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

    <!-- SOL: Stok Giriş Formu -->
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-box-arrow-in-down me-2"></i>
                    <?= __('new_stock_entry') ?>
                </h5>
            </div>
            <div class="panel-body">

                <form method="POST" action="entry.php" data-once id="entryForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="product_id" id="selectedProductId">

                    <!-- Ürün Arama -->
                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('select_product') ?> <span style="color:#ef9a9a">*</span>
                        </label>
                        <input type="text" id="productSearchInput" class="form-control-dark"
                            placeholder="<?= __('search_product') ?>" autocomplete="off">
                        <div id="productDropdown" style="display:none;position:relative;z-index:99;background:#162333;
                                    border:1px solid rgba(255,255,255,0.1);border-radius:8px;
                                    margin-top:4px;max-height:240px;overflow-y:auto;">
                        </div>
                    </div>

                    <!-- Seçilen ürün bilgisi -->
                    <div id="selectedProductInfo" style="display:none;" class="mb-3">
                        <div class="p-3"
                            style="background:rgba(14,165,233,0.08);border:1px solid rgba(14,165,233,0.2);border-radius:10px;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong id="spName" style="font-size:15px;">—</strong>
                                    <div style="font-size:12px;color:var(--text-muted);">
                                        <span id="spForm"></span> | <span id="spCat"></span>
                                    </div>
                                    <div class="mt-2 d-flex gap-3" style="font-size:13px;">
                                        <span><?= __('stock') ?>: <strong id="spStock"
                                                style="color:var(--accent);">0</strong> <span
                                                id="spUnit">—</span></span>
                                        <span><?= __('critical') ?>: <strong id="spCritical"
                                                style="color:var(--warning);">0</strong></span>
                                    </div>
                                    <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">
                                        <?= __('purchase_price') ?>: <span id="spPurchase">0</span> <span
                                            id="spCur1"></span> | <?= __('sale_price') ?>: <span id="spSale">0</span>
                                        <span id="spCur2"></span>
                                    </div>
                                </div>
                                <button type="button" class="btn-sm-icon btn-delete" onclick="clearProduct()"
                                    title="<?= __('edit') ?>">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Miktar -->
                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('entry_qty') ?> <span
                                style="color:#ef9a9a">*</span></label>
                        <input type="number" name="quantity" class="form-control-dark" min="1" required
                            placeholder="..." style="font-size:18px;padding:14px;">
                    </div>

                    <!-- Fatura / İrsaliye No -->
                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('invoice_delivery_no') ?>
                        </label>
                        <input type="text" name="reference" class="form-control-dark" placeholder="e.g. FTR20260042">
                    </div>

                    <!-- Alış Fiyatı Güncelleme -->
                    <div class="mb-3 p-3"
                        style="background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.15);border-radius:10px;">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="updatePriceCheck" name="update_price"
                                value="1" onchange="togglePriceField()">
                            <label class="form-check-label text-white-50" for="updatePriceCheck"
                                style="font-size:13px;">
                                <?= __('update_purchase_price') ?>
                            </label>
                        </div>
                        <div id="priceField" style="display:none;">
                            <input type="number" name="purchase_price" id="purchasePriceInput" class="form-control-dark"
                                step="0.01" min="0" placeholder="<?= __('purchase_price') ?>">
                            <small class="text-muted" id="priceCurrencyHint"
                                style="font-size:11px; margin-top:4px; display:block;"></small>
                        </div>
                    </div>

                    <!-- Görsel Güncelleme -->
                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('product_image') ?><span
                                style="color:var(--text-muted);font-size:11px;font-weight:normal;">
                                <?= __('optional_image_update') ?>
                            </span>
                        </label>
                        <input type="file" name="image" class="form-control-dark"
                            accept="image/jpeg,image/png,image/webp">
                    </div>

                    <!-- Not -->
                    <div class="mb-4">
                        <label class="form-label-dark">
                            <?= __('note') ?>
                        </label>
                        <input type="text" name="note" class="form-control-dark"
                            placeholder="<?= __('explanation_supplier_info') ?>">
                    </div>

                    <button type="submit" class="btn-accent w-100" style="padding:14px;font-size:15px;">
                        <i class="bi bi-box-arrow-in-down me-2"></i>
                        <?= __('perform_stock_entry') ?>
                    </button>
                </form>

            </div>
        </div>
    </div>

    <!-- SAĞ: Son Girişler -->
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-clock-history me-2"></i>
                    <?= __('recent_stock_entries') ?><span class="badge bg-secondary ms-2">
                        <?= count($recentEntries) ?>
                    </span>
                </h5>
                <a href="movements.php?type=in" class="btn btn-outline-secondary btn-sm">
                    <?= __('view_all') ?>
                </a>
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
                                <?= __('reference') ?>
                            </th>
                            <th>
                                <?= __('note') ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentEntries)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                                    <?= __('no_stock_entries') ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentEntries as $e): ?>
                                <tr>
                                    <td style="font-size:12px;white-space:nowrap;">
                                        <?= date('d.m.Y', strtotime($e['created_at'])) ?>
                                        <div style="color:var(--text-muted);">
                                            <?= date('H:i', strtotime($e['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td><strong style="font-size:13px;">
                                            <?= e($e['product_name']) ?>
                                        </strong></td>
                                    <td>
                                        <span class="badge-stock-ok" style="font-size:13px;">
                                            +
                                            <?= (int) $e['quantity'] ?>
                                            <?= e($e['unit']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:12px;color:var(--text-muted);">
                                        <?= e($e['reference'] ?? '—') ?>
                                    </td>
                                    <td style="font-size:12px;color:var(--text-muted);max-width:200px;">
                                        <?= e($e['note'] ?? '—') ?>
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

<script>
    // Ürün verilerini JS'e aktar
    const allProducts = <?= json_encode(array_map(function ($p) {
        return [
            'id' => (int) $p['id'],
            'name' => $p['name'],
            'barcode' => $p['barcode'],
            'dosage_form' => $p['dosage_form'],
            'cat_name' => $p['cat_name'],
            'stock_quantity' => (int) $p['stock_quantity'],
            'critical_stock' => (int) $p['critical_stock'],
            'unit' => $p['unit'],
            'purchase_price' => (float) $p['purchase_price'],
            'sale_price' => (float) $p['sale_price'],
            'currency' => $p['currency']
        ];
    }, $allProducts)) ?>;

    const searchInput = document.getElementById('productSearchInput');
    const dropdown = document.getElementById('productDropdown');

    searchInput.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        if (q.length < 1) { dropdown.style.display = 'none'; return; }

        const terms = q.split(/\s+/).filter(t => t.length > 0);
        const filtered = allProducts.filter(p => {
            const text = (p.name + ' ' + (p.barcode || '')).toLowerCase();
            return terms.every(t => text.includes(t));
        }).slice(0, 20);

        if (filtered.length === 0) {
            dropdown.innerHTML = `<div style="padding:14px;color:var(--text-muted);font-size:13px;"><?= __('no_data') ?></div>`;
        } else {
            let html = '';
            filtered.forEach(p => {
                html += `
            <div onclick="selectProduct(${p.id})"
                 style="padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid rgba(255,255,255,0.04);
                        display:flex;justify-content:space-between;align-items:center;"
                 onmouseover="this.style.background='rgba(14,165,233,0.1)'"
                 onmouseout="this.style.background=''">
                <div>
                    <strong>${p.name}</strong>
                    <div style="font-size:11px;color:var(--text-muted);">${p.dosage_form || ''} ${p.cat_name ? '• ' + p.cat_name : ''}</div>
                </div>
                <div style="text-align:right;font-size:12px;">
                    <span style="color:${p.stock_quantity <= 0 ? 'var(--danger)' : p.stock_quantity <= p.critical_stock ? 'var(--warning)' : 'var(--success)'};"><?= __('stock') ?>: ${p.stock_quantity} ${p.unit}</span>
                </div>
            </div>`;
            });
            dropdown.innerHTML = html;
        }
        dropdown.style.display = 'block';
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#productSearchInput') && !e.target.closest('#productDropdown')) {
            dropdown.style.display = 'none';
        }
    });

    function selectProduct(id) {
        const p = allProducts.find(x => x.id === id);
        if (!p) return;

        document.getElementById('selectedProductId').value = p.id;
        document.getElementById('spName').textContent = p.name;
        document.getElementById('spForm').textContent = p.dosage_form || '—';
        document.getElementById('spCat').textContent = p.cat_name || '—';
        document.getElementById('spStock').textContent = p.stock_quantity;
        document.getElementById('spUnit').textContent = p.unit;
        document.getElementById('spCritical').textContent = p.critical_stock;

        document.getElementById('spPurchase').textContent = p.purchase_price.toFixed(2);
        document.getElementById('spCur1').textContent = p.currency || 'USD';
        document.getElementById('spSale').textContent = p.sale_price.toFixed(2);
        document.getElementById('spCur2').textContent = p.currency || 'USD';

        document.getElementById('productSearchInput').parentElement.style.display = 'none';
        document.getElementById('selectedProductInfo').style.display = 'block';
        dropdown.style.display = 'none';

        // Alış fiyatı ipucu
        const curr = p.currency || 'USD';
        document.getElementById('priceCurrencyHint').textContent = `* Current purchase price: ${p.purchase_price.toFixed(2)} ${curr}`;
    }

    function clearProduct() {
        document.getElementById('selectedProductId').value = '';
        document.getElementById('selectedProductInfo').style.display = 'none';
        const inp = document.getElementById('productSearchInput');
        inp.value = '';
        inp.parentElement.style.display = 'block';
        inp.focus();
    }

    function togglePriceField() {
        const check = document.getElementById('updatePriceCheck');
        const box = document.getElementById('priceField');
        if (check.checked) {
            box.style.display = 'block';
            document.getElementById('purchasePriceInput').required = true;
        } else {
            box.style.display = 'none';
            document.getElementById('purchasePriceInput').required = false;
        }
    }
</script>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>