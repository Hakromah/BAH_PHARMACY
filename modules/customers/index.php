<?php
/**
 * Customer List
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();

// ── Filtreler ──────────────────────────────────────────
$search = get('search');
$debtFilter = get('debt'); // all | has_debt | no_debt

$where = ['1=1'];
$params = [];

if ($search !== '') {
    $gSearch = buildGoogleSearchQuery(['c.first_name', 'c.last_name', 'c.phone'], $search, 'csch');
    if (!empty($gSearch['sql'])) {
        $where[] = $gSearch['sql'];
        $params = array_merge($params, $gSearch['params']);
    }
}
if ($debtFilter === 'has_debt') {
    $where[] = 'c.total_debt > 0';
} elseif ($debtFilter === 'no_debt') {
    $where[] = 'c.total_debt <= 0';
}

$whereStr = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT c.*, 
        (SELECT COUNT(*) FROM sales WHERE customer_id = c.id) as sale_count
    FROM customers c
    WHERE $whereStr
    ORDER BY c.first_name ASC, c.last_name ASC
");
$stmt->execute($params);
$customers = $stmt->fetchAll();

// İstatistik
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(total_debt > 0) AS with_debt
    FROM customers
")->fetch();

$totalDebt = sumConverted("SELECT total_debt as val, currency FROM customers WHERE total_debt > 0", 'val', 'currency');

$pageTitle = __('customers');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>
<!-- İstatistik Kartları -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-people"></i></div>
        <div>
            <div class="stat-label">
                <?= __('total_customers') ?>
            </div>
            <div class="stat-value">
                <?= (int) $stats['total'] ?>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-person-exclamation"></i></div>
        <div>
            <div class="stat-label">
                <?= __('has_debt') ?>
            </div>
            <div class="stat-value">
                <?= (int) $stats['with_debt'] ?>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-credit-card"></i></div>
        <div>
            <div class="stat-label">
                <?= __('total_debt') ?>
            </div>
            <div class="stat-value" style="font-size:16px;">
                <?= formatMoney((float) $totalDebt) ?>
            </div>
        </div>
    </div>
</div>

<!-- Filtre Paneli -->
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label-dark">
                    <?= __('search_customer') ?>
                </label>
                <input type="text" name="search" class="form-control-dark" placeholder="<?= __('search_customer') ?>"
                    value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label-dark">
                    <?= __('status') ?>
                </label>
                <select name="debt" class="form-select-dark">
                    <option value="" <?= $debtFilter === '' ? 'selected' : '' ?>>
                        <?= __('all') ?>
                    </option>
                    <option value="has_debt" <?= $debtFilter === 'has_debt' ? 'selected' : '' ?>>
                        <?= __('has_debt') ?>
                    </option>
                    <option value="no_debt" <?= $debtFilter === 'no_debt' ? 'selected' : '' ?>>
                        <?= __('no_debt') ?>
                    </option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn-accent"><i class="bi bi-search"></i>
                    <?= __('search') ?>
                </button>
                <a href="index.php" class="btn btn-outline-secondary btn-sm align-self-end">
                    <?= __('all') ?>
                </a>
                <a href="fast_payment.php" class="btn btn-success ms-2 px-3" style="font-weight:600;">
                    <i class="bi bi-cash-coin me-1"></i>
                    <?= __('fast_payment') ?>

                </a>
                <a href="receivables.php" class="btn btn-warning ms-2 px-3" style="font-weight:600; color:#000;">
                    <i class="bi bi-calendar-check me-1"></i>
                    <?= __('receivables_list') ?>
                </a>
                <a href="form.php" class="btn-accent ms-2">
                    <i class="bi bi-person-plus me-1"></i>
                    <?= __('new_customer') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Müşteri Tablosu -->
<div class="panel">
    <div class="panel-header">
        <h5><i class="bi bi-people me-2"></i>
            <?= __('customers') ?><span class="badge bg-secondary ms-2">
                <?= count($customers) ?>
            </span>
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table-dark-custom">
            <thead>
                <tr>
                    <th>
                        <?= __('first_name') ?> /
                        <?= __('last_name') ?>
                    </th>
                    <th>
                        <?= __('phone') ?>
                    </th>
                    <th>
                        <?= __('due_days') ?>
                    </th>
                    <th>
                        <?= __('sale_count') ?>
                    </th>
                    <th>
                        <?= __('debit_credit') ?>
                    </th>
                    <th>
                        <?= __('date') ?>
                    </th>
                    <th>
                        <?= __('actions') ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5" style="color:var(--text-muted);">
                            <i class="bi bi-person-slash" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                            <?= __('no_data') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr class="<?= $c['total_debt'] > 0 ? 'row-low' : '' ?>">
                            <td>
                                <a href="detail.php?id=<?= $c['id'] ?>"
                                    style="color:var(--text-primary);text-decoration:none;font-weight:600;"><i
                                        class="bi bi-person-circle me-1" style="color:var(--accent);"></i>
                                    <?= e($c['first_name'] . ' ' . $c['last_name']) ?>
                                </a>
                                <div style="font-size:11px;color:var(--text-muted);"><?= __('profile_id') ?>:
                                    <?= e($c['unique_id']) ?>
                                </div>
                            </td>
                            <td>
                                <?= e($c['phone'] ?? '—') ?>
                            </td>
                            <td>
                                <?= (int) $c['payment_due_days'] ?>         <?= __('days') ?>
                            </td>
                            <td>
                                <span class="badge-stock-ok">
                                    <?= (int) $c['sale_count'] ?>         <?= __('quantity') ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $debtVal = (float) $c['total_debt'];
                                if ($debtVal > 0.001): ?>
                                    <div class="d-flex align-items-center" style="color:var(--danger); font-weight:700;">
                                        <span class="me-2 d-flex align-items-center justify-content-center"
                                            style="width:20px;height:20px;background:rgba(239,68,68,0.15);border:1px solid var(--danger);border-radius:4px;">
                                            <i class="bi bi-dash"></i>
                                        </span>
                                        <?= formatMoney($debtVal) ?>
                                    </div>
                                <?php elseif ($debtVal < -0.001): ?>
                                    <div class="d-flex align-items-center" style="color:var(--success); font-weight:700;">
                                        <span class="me-2 d-flex align-items-center justify-content-center"
                                            style="width:20px;height:20px;background:rgba(34,197,94,0.15);border:1px solid var(--success);border-radius:4px;">
                                            <i class="bi bi-plus"></i>
                                        </span>
                                        <?= formatMoney(abs($debtVal)) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted);">
                                <?= date('d.m.Y', strtotime($c['created_at'])) ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="fast_payment.php?customer_id=<?= $c['id'] ?>" class="btn-sm-icon"
                                        title="<?= __('fast_payment') ?>" style="color:var(--success);">
                                        <i class="bi bi-cash-coin"></i>
                                    </a>
                                    <a href="detail.php?id=<?= $c['id'] ?>" class="btn-sm-icon" title="<?= __('detail') ?>"
                                        style="color:var(--info);">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="form.php?id=<?= $c['id'] ?>" class="btn-sm-icon btn-edit"
                                        title="<?= __('edit') ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="delete.php?id=<?= $c['id'] ?>" class="btn-sm-icon btn-delete"
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