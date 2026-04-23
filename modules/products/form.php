<?php
/**
 * Product Add / Edit Form
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();

$id      = (int) get('id');
$product = null;
$errors  = [];

// ── Mevcut ürünü yükle (düzenleme modu) ─────────────────
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();
    if (!$product) {
        setFlash('error', __('error_not_found'));
        redirect(BASE_URL . '/modules/products/index.php');
    }
}

// ── POST: Kaydet / Güncelle ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die(__('error'));
    }

    $data = [
        'name'           => post('name'),
        'barcode'        => post('barcode') !== '' ? post('barcode') : null,
        'sku'            => post('sku') !== '' ? post('sku') : null,
        'dosage_form'    => post('dosage_form'),
        'category_id'   => post('category_id') !== '' ? (int) post('category_id') : null,
        'currency'       => post('currency', 'USD'),
        'purchase_price' => max(0, (float) post('purchase_price')),
        'sale_price'     => max(0, (float) post('sale_price')),
        'unit'           => post('unit', 'Box'),
        'stock_quantity' => (int) post('stock_quantity'),
        'critical_stock' => max(0, (int) post('critical_stock')),
        'is_active'      => isset($_POST['is_active']) ? 1 : 0,
    ];

    // Doğrulama
    if (empty($data['name']))       $errors[] = __('name_required');
    if ($data['sale_price'] <= 0)   $errors[] = __('price_required');

    // Barkod benzersizlik kontrolü
    if ($data['barcode'] !== null) {
        $s = $pdo->prepare("SELECT id FROM products WHERE barcode = :b AND id != :id");
        $s->execute([':b' => $data['barcode'], ':id' => $id]);
        if ($s->fetch()) {
            $errors[] = __('barcode_exists');
        }
    }

    // Görsel yükleme
    if (empty($errors)) {
        $imageName = $product['image'] ?? null;

        if (!empty($_FILES['image']['name'])) {
            $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowed)) {
                $errors[] = __('invalid_image_format');
            } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
                $errors[] = __('image_size_error');
            } else {
                $imageName = uniqid('img_') . '.' . $ext;
                $dest      = dirname(__DIR__, 2) . '/storage/images/' . $imageName;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $errors[] = __('image_upload_failed');
                    $imageName = $product['image'] ?? null;
                }
            }
        }
    }

    if (empty($errors)) {
        if ($id > 0) {
            // GÜNCELLE
            $stmt = $pdo->prepare("
                UPDATE products SET
                    name = :name, barcode = :barcode, sku = :sku,
                    dosage_form = :dosage_form, category_id = :category_id,
                    currency = :currency, purchase_price = :purchase_price,
                    sale_price = :sale_price, unit = :unit,
                    stock_quantity = :stock_quantity, critical_stock = :critical_stock,
                    is_active = :is_active, image = :image
                WHERE id = :id
            ");
            $stmt->execute(array_merge($data, [':image' => $imageName, ':id' => $id]));
            logAction('Product updated', "Product ID: #$id");
            setFlash('success', __('product_updated'));
        } else {
            // EKLE
            $stmt = $pdo->prepare("
                INSERT INTO products
                    (name, barcode, sku, dosage_form, category_id, currency,
                     purchase_price, sale_price, unit, stock_quantity, critical_stock, is_active, image)
                VALUES
                    (:name, :barcode, :sku, :dosage_form, :category_id, :currency,
                     :purchase_price, :sale_price, :unit, :stock_quantity, :critical_stock, :is_active, :image)
            ");
            $stmt->execute(array_merge($data, [':image' => $imageName]));
            $newId = $pdo->lastInsertId();
            logAction('New Product Added', "Product ID: #$newId — " . $data['name']);
            setFlash('success', __('product_added'));
        }
        redirect(BASE_URL . '/modules/products/index.php');
    }

    // Hata varsa form değerlerini koru
    if ($product) {
        $product = array_merge($product, $data);
    }
}

// ── Seçim listeleri ──────────────────────────────────────
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$currencies  = $pdo->query("SELECT code, symbol FROM currencies WHERE is_active = 1 ORDER BY code ASC")->fetchAll();

$pageTitle = $id > 0 ? __('edit_product') : __('new_product');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><?= __('error') ?></strong>
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h5><i class="bi bi-<?= $id > 0 ? 'pencil' : 'plus-circle' ?> me-2"></i><?= e($pageTitle) ?></h5>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i><?= __('back') ?></a>
    </div>
    <div class="panel-body">

        <form method="POST" action="form.php<?= $id > 0 ? '?id=' . $id : '' ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <div class="row g-4">

                <!-- SOL: Temel Bilgiler -->
                <div class="col-md-8">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label-dark"><?= __('product_name') ?><span style="color:#ef9a9a">*</span></label>
                            <input type="text" name="name" class="form-control-dark"
                                   value="<?= e($product['name'] ?? post('name')) ?>"
                                   placeholder="<?= __('product_name') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('barcode') ?></label>
                            <input type="text" name="barcode" class="form-control-dark"
                                   value="<?= e($product['barcode'] ?? post('barcode')) ?>"
                                   placeholder="<?= __('barcode') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('sku') ?></label>
                            <input type="text" name="sku" class="form-control-dark"
                                   value="<?= e($product['sku'] ?? post('sku')) ?>"
                                   placeholder="<?= __('sku') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('dosage_form') ?></label>
                            <input type="text" name="dosage_form" class="form-control-dark"
                                   list="dosage-list"
                                   value="<?= e($product['dosage_form'] ?? post('dosage_form')) ?>"
                                   placeholder="<?= __('dosage_form') ?>">
                            <datalist id="dosage-list">
                                <option><?= __('tablet') ?></option>
                                <option><?= __('capsule') ?></option>
                                <option><?= __('syrup') ?></option>
                                <option><?= __('ampoule') ?></option>
                                <option><?= __('cream') ?></option>
                                <option><?= __('drops') ?></option>
                                <option><?= __('spray') ?></option>
                                <option><?= __('ointment') ?></option>
                                <option><?= __('suppository') ?></option>
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('category') ?></label>
                            <select name="category_id" class="form-select-dark">
                                <option value=""><?= __('all') ?></option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"
                                        <?= ($product['category_id'] ?? post('category_id')) == $cat['id'] ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label-dark"><?= __('currency') ?></label>
                            <select name="currency" class="form-select-dark">
                                <?php foreach ($currencies as $c): ?>
                                    <option value="<?= e($c['code']) ?>"
                                        <?= ($product['currency'] ?? post('currency', 'USD')) === $c['code'] ? 'selected' : '' ?>>
                                        <?= e($c['code']) ?> (<?= e($c['symbol']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-dark"><?= __('purchase_price') ?></label>
                            <input type="number" name="purchase_price" class="form-control-dark"
                                   step="0.01" min="0"
                                   value="<?= e((string)($product['purchase_price'] ?? post('purchase_price', '0'))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-dark"><?= __('sale_price') ?><span style="color:#ef9a9a">*</span></label>
                            <input type="number" name="sale_price" class="form-control-dark"
                                   step="0.01" min="0.01" required
                                   value="<?= e((string)($product['sale_price'] ?? post('sale_price', '0'))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-dark"><?= __('unit') ?></label>
                            <select name="unit" class="form-select-dark">
                                <?php foreach (['Box', 'Piece', 'Bottle', 'Tube', 'Package', 'Blister'] as $u): ?>
                                    <option <?= ($product['unit'] ?? 'Box') === $u ? 'selected' : '' ?>><?= $u ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('stock_quantity') ?></label>
                            <input type="number" name="stock_quantity" class="form-control-dark"
                                   min="0"
                                   value="<?= e((string)($product['stock_quantity'] ?? post('stock_quantity', '0'))) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-dark"><?= __('critical_stock') ?></label>
                            <input type="number" name="critical_stock" class="form-control-dark"
                                   min="0"
                                   value="<?= e((string)($product['critical_stock'] ?? post('critical_stock', '5'))) ?>">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       id="is_active" name="is_active" value="1"
                                       <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label text-white-50" for="is_active">
                                    <?= __('active') ?>
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- SAĞ: Görsel -->
                <div class="col-md-4">
                    <label class="form-label-dark"><?= __('product_image') ?></label>

                    <?php if (!empty($product['image'])): ?>
                        <div class="mb-3 text-center">
                            <img id="image-preview"
                                 src="<?= BASE_URL ?>/storage/images/<?= e($product['image']) ?>"
                                 style="max-width:100%;border-radius:12px;max-height:200px;object-fit:cover;">
                        </div>
                    <?php else: ?>
                        <div class="mb-3 text-center text-muted">
                            <?php if (file_exists(dirname(__DIR__, 2) . '/storage/images/placeholder.png')): ?>
                                <img id="image-preview"
                                     src="<?= BASE_URL ?>/storage/images/placeholder.png?v=<?= time() ?>"
                                     style="max-width:100%;border-radius:12px;max-height:200px;">
                            <?php else: ?>
                                <span id="emoji-preview" style="font-size:64px;display:block;">💊</span>
                                <img id="image-preview" src="" alt=""
                                     style="display:none;max-width:100%;border-radius:12px;max-height:200px;object-fit:cover;">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <label class="upload-zone" for="image">
                        <i class="bi bi-cloud-upload"></i>
                        <span><?= __('select_or_drop_image') ?></span>
                        <small style="display:block;margin-top:4px;font-size:11px;">JPG, PNG, WebP — max 3MB</small>
                        <input type="file" name="image" id="image"
                               accept="image/*" style="display:none;"
                               data-preview="image-preview">
                    </label>
                </div>

            </div><!-- /.row -->

            <hr style="border-color:rgba(255,255,255,0.08);margin:24px 0;">

            <div class="d-flex gap-3">
                <button type="submit" class="btn-accent px-4">
                    <i class="bi bi-check-lg me-1"></i>
                    <?= $id > 0 ? __('update') : __('save') ?>
                </button>
                <a href="index.php" class="btn btn-outline-secondary"><?= __('cancel') ?></a>
            </div>

        </form>

    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>
