<?php
/**
 * Product List
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();

// ── Filtreler ──────────────────────────────────────────
$search = get('search');
$categoryId = get('category_id');
$stockFilter = get('stock'); // all | low | out

// ── Sorgu ──────────────────────────────────────────────
$where = ['1=1'];
$params = [];

if ($search !== '') {
    $gSearch = buildGoogleSearchQuery(['p.name', 'p.barcode', 'p.sku'], $search, 'psch');
    if (!empty($gSearch['sql'])) {
        $where[] = $gSearch['sql'];
        $params = array_merge($params, $gSearch['params']);
    }
}
if ($categoryId !== '') {
    $where[] = 'p.category_id = :cat';
    $params[':cat'] = (int) $categoryId;
}
if ($stockFilter === 'low') {
    $where[] = 'p.stock_quantity > 0 AND p.stock_quantity <= p.critical_stock';
} elseif ($stockFilter === 'out') {
    $where[] = 'p.stock_quantity <= 0';
}

$whereStr = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE $whereStr
    ORDER BY p.name ASC
");
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// İstatistik
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(stock_quantity <= 0) AS out_of_stock,
        SUM(stock_quantity > 0 AND stock_quantity <= critical_stock) AS low_stock
    FROM products WHERE is_active = 1
")->fetch();

$stockValue = sumConverted("SELECT (stock_quantity * purchase_price) as val, currency FROM products WHERE is_active = 1 AND stock_quantity > 0", 'val', 'currency');

$pageTitle = __('products');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>
<!-- İstatistik Kartları -->
<div class="stat-cards">
    <div class="stat-card" onclick="window.location.href='index.php'" style="cursor:pointer;"
        title="Tüm Ürünleri Listele">
        <div class="stat-icon blue"><i class="bi bi-box-seam"></i></div>
        <div>
            <div class="stat-label">
                <?= __('total_products') ?>
            </div>
            <div class="stat-value">
                <?= (int) $stats['total'] ?>
            </div>
        </div>
    </div>
    <div class="stat-card" onclick="window.location.href='index.php?stock=low'" style="cursor:pointer;"
        title="Kritik Stokları Listele">
        <div class="stat-icon orange"><i class="bi bi-exclamation-triangle"></i></div>
        <div>
            <div class="stat-label">
                <?= __('critical_stock') ?>
            </div>
            <div class="stat-value">
                <?= (int) $stats['low_stock'] ?>
            </div>
        </div>
    </div>
    <div class="stat-card" onclick="window.location.href='index.php?stock=out'" style="cursor:pointer;"
        title="Tükenenleri Listele">
        <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
        <div>
            <div class="stat-label">
                <?= __('out_of_stock') ?>
            </div>
            <div class="stat-value">
                <?= (int) $stats['out_of_stock'] ?>
            </div>
        </div>
    </div>
    <div class="stat-card" style="cursor:default;">
        <div class="stat-icon green"><i class="bi bi-currency-dollar"></i></div>
        <div>
            <div class="stat-label">
                <?= __('stock_value') ?>
            </div>
            <div class="stat-value">
                <?= formatMoney((float) $stockValue) ?>
            </div>
        </div>
    </div>
</div>

<!-- Filtre & Arama Paneli -->
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <!-- Arama -->
            <div class="col-md-4">
                <label class="form-label-dark">
                    <?= __('search') ?>
                </label>
                <input type="text" name="search" class="form-control-dark" placeholder="<?= __('search_product') ?>"
                    value="<?= e($search) ?>">
            </div>
            <!-- Kategori -->
            <div class="col-md-3">
                <label class="form-label-dark">
                    <?= __('category') ?>
                </label>
                <select name="category_id" class="form-select-dark">
                    <option value="">
                        <?= __('all') ?>
                    </option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Stok Durumu -->
            <div class="col-md-3">
                <label class="form-label-dark">
                    <?= __('status') ?>
                </label>
                <select name="stock" class="form-select-dark">
                    <option value="" <?= $stockFilter === '' ? 'selected' : '' ?>>
                        <?= __('all') ?>
                    </option>
                    <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>
                        <?= __('critical') ?>
                    </option>
                    <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>
                        <?= __('out_of_stock') ?>
                    </option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn-accent w-100"><i class="bi bi-search"></i>
                    <?= __('search') ?>
                </button>
                <a href="index.php" class="btn btn-outline-secondary btn-sm align-self-end">
                    <?= __('all') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Ürün Tablosu -->
<div class="panel">
    <div class="panel-header">
        <h5><i class="bi bi-box-seam me-2"></i>
            <?= __('products') ?><span class="badge bg-secondary ms-2">
                <?= count($products) ?>
            </span>
        </h5>
        <div class="d-flex gap-2">
            <a href="import_export.php" class="btn btn-success"><i class="bi bi-arrow-down-up me-1"></i>
                <?= __('import_export') ?></a>
            <a href="form.php" class="btn-accent"><i class="bi bi-plus-lg"></i>
                <?= __('add') ?>
            </a>
            <a href="<?= BASE_URL ?>/modules/products/categories.php" class="btn btn-outline-info"><i
                    class="bi bi-tags me-1"></i>
                <?= __('categories') ?>
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table-dark-custom">
            <thead>
                <tr>
                    <th>
                        <?= __('product_image') ?>
                    </th>
                    <th>
                        <?= __('product_name') ?>
                    </th>
                    <th>
                        <?= __('barcode') ?>
                    </th>
                    <th>
                        <?= __('dosage_form') ?>
                    </th>
                    <th>
                        <?= __('category') ?>
                    </th>
                    <th>
                        <?= __('purchase_price') ?>
                    </th>
                    <th>
                        <?= __('sale_price') ?>
                    </th>
                    <th>
                        <?= __('stock_qty') ?>
                    </th>
                    <th>
                        <?= __('status') ?>
                    </th>
                    <th>
                        <?= __('actions') ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-5" style="color:var(--text-muted);">
                            <i class="bi bi-inbox" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                            <?= __('no_data') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p):
                        $isOut = $p['stock_quantity'] <= 0;
                        $isLow = !$isOut && $p['stock_quantity'] <= $p['critical_stock'];
                        $rowCls = $isOut ? 'row-critical' : ($isLow ? 'row-low' : '');
                        ?>
                        <tr class="<?= $rowCls ?>">
                            <td>
                                <?php if ($p['image'] && file_exists(dirname(__DIR__, 2) . '/storage/images/' . $p['image'])): ?>
                                    <img src="<?= BASE_URL ?>/storage/images/<?= e($p['image']) ?>" class="product-img"
                                        alt="<?= e($p['name']) ?>">
                                <?php else: ?>
                                    <?php if (file_exists(dirname(__DIR__, 2) . '/storage/images/placeholder.png')): ?>
                                        <img src="<?= BASE_URL ?>/storage/images/placeholder.png?v=<?= time() ?>" class="product-img"
                                            alt="placeholder">
                                    <?php else: ?>
                                        <span class="product-img-placeholder">💊</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>
                                    <?= e($p['name']) ?>
                                </strong>
                                <?php if ($p['sku']): ?>
                                    <div class="text-muted" style="font-size:11px;">
                                        <?= __('sku') ?>:
                                        <?= e($p['sku']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= e($p['barcode'] ?? '—') ?>
                            </td>
                            <td>
                                <?= e($p['dosage_form'] ?? '—') ?>
                            </td>
                            <td>
                                <?= e($p['category_name'] ?? '—') ?>
                            </td>
                            <td>
                                <?= formatMoney((float) $p['purchase_price'], $p['currency'] ?? 'USD') ?>
                            </td>
                            <td>
                                <?= formatMoney((float) $p['sale_price'], $p['currency'] ?? 'USD') ?>
                            </td>
                            <td>
                                <strong>
                                    <?= (int) $p['stock_quantity'] ?>
                                </strong>
                                <span class="text-muted" style="font-size:11px;">
                                    <?= e($p['unit'] ?? '') ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($isOut): ?>
                                    <span class="badge-stock-out">
                                        <?= __('out_of_stock') ?>
                                    </span>
                                <?php elseif ($isLow): ?>
                                    <span class="badge-stock-low">
                                        <?= __('critical') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge-stock-ok">
                                        <?= __('sufficient') ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="form.php?id=<?= $p['id'] ?>" class="btn-sm-icon btn-edit"
                                        title="<?= __('edit') ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="stock_update.php?id=<?= $p['id'] ?>" class="btn-sm-icon btn-edit"
                                        title="<?= __('stock_convert') ?>">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </a>
                                    <a href="delete.php?id=<?= $p['id'] ?>" class="btn-sm-icon btn-delete"
                                        data-confirm="<?= __('confirm_delete') ?>" title="<?= __('delete') ?>">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>