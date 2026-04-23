<?php
/**
 * Stock Movements List
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();

// Filtreler
$productId = get('product_id');
$type = get('type');
$dateFrom = get('date_from');
$dateTo = get('date_to');

$where = ['1=1'];
$params = [];

if ($productId !== '') {
    $where[] = 'sm.product_id = :pid';
    $params[':pid'] = (int) $productId;
}
if ($type !== '') {
    $where[] = 'sm.type = :type';
    $params[':type'] = $type;
}
if ($dateFrom !== '') {
    $where[] = 'DATE(sm.created_at) >= :df';
    $params[':df'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'DATE(sm.created_at) <= :dt';
    $params[':dt'] = $dateTo;
}

$whereStr = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT sm.*, p.name as product_name, p.unit 
    FROM stock_movements sm 
    JOIN products p ON sm.product_id = p.id 
    WHERE {$whereStr} 
    ORDER BY sm.created_at DESC 
    LIMIT 200
");
$stmt->execute($params);
$movements = $stmt->fetchAll();

$products = $pdo->query("SELECT id, name FROM products WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

$typeLabels = [
    'in' => [__('movement_in'), 'badge-stock-ok'],
    'out' => [__('movement_out'), 'badge-stock-out'],
    'adjust' => [__('movement_adjust'), 'badge-stock-low'],
    'convert' => [__('movement_convert'), 'badge-stock-low']
];
$pageTitle = __('stock_movements');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>
<!-- Filtre -->
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="movements.php" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label-dark">
                    <?= __('product') ?>
                </label>
                <select name="product_id" class="form-select-dark">
                    <option value="">
                        <?= __('all') ?>
                    </option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $productId == $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label-dark">
                    <?= __('type') ?>
                </label>
                <select name="type" class="form-select-dark">
                    <option value="">
                        <?= __('all') ?>
                    </option>
                    <option value="in" <?= $type === 'in' ? 'selected' : '' ?>><?= __('movement_in') ?></option>
                    <option value="out" <?= $type === 'out' ? 'selected' : '' ?>><?= __('movement_out') ?></option>
                    <option value="adjust" <?= $type === 'adjust' ? 'selected' : '' ?>><?= __('movement_adjust') ?>
                    </option>
                    <option value="convert" <?= $type === 'convert' ? 'selected' : '' ?>><?= __('movement_convert') ?>
                    </option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label-dark">
                    <?= __('date_start') ?>
                </label>
                <input type="date" name="date_from" class="form-control-dark" value="<?= e($dateFrom) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label-dark">
                    <?= __('date_end') ?>
                </label>
                <input type="date" name="date_to" class="form-control-dark" value="<?= e($dateTo) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn-accent"><i class="bi bi-search"></i>
                    <?= __('filter') ?>
                </button>
                <a href="movements.php" class="btn btn-outline-secondary btn-sm align-self-end">
                    <?= __('all') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tablo -->
<div class="panel">
    <div class="panel-header">
        <h5><i class="bi bi-arrow-left-right me-2"></i>
            <?= __('stock_movements') ?><span class="badge bg-secondary ms-2">
                <?= count($movements) ?>
            </span>
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table-dark-custom">
            <thead>
                <tr>
                    <th>#</th>
                    <th>
                        <?= __('date') ?>
                    </th>
                    <th>
                        <?= __('product') ?>
                    </th>
                    <th>
                        <?= __('type') ?>
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
                <?php if (empty($movements)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
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
                            <td style="color:var(--text-muted);">
                                <?= $m['id'] ?>
                            </td>
                            <td style="font-size:13px;">
                                <?= date('d.m.Y H:i', strtotime($m['created_at'])) ?>
                            </td>
                            <td><strong>
                                    <?= e($m['product_name']) ?>
                                </strong></td>
                            <td><span class="<?= $cls ?>">
                                    <?= $label ?>
                                </span></td>
                            <td>
                                <strong style="color:<?= $m['quantity'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                                    <?= $sign . (int) $m['quantity'] ?>
                                </strong>
                                <span class="text-muted" style="font-size:11px;">
                                    <?= e($m['unit']) ?>
                                </span>
                            </td>
                            <td style="font-size:13px;color:var(--text-muted);">
                                <?= e($m['reference'] ?? '—') ?>
                            </td>
                            <td style="font-size:13px;color:var(--text-muted);">
                                <?= e($m['note'] ?? '—') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>