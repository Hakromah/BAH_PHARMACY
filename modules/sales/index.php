<?php
/**
 * Sales List
 *
 * Date range, customer and payment status filter.
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();

// ── Filtreler ──────────────────────────────────────────
$search = get('search');
$dateFrom = get('date_from');
$dateTo = get('date_to');
$debtOnly = get('debt_only');   // 1 = sadece borçlu

$where = ['1=1'];
$params = [];

if ($search !== '') {
    $where[] = "(CONCAT(c.first_name,' ',c.last_name) LIKE :s OR s.id LIKE :s)";
    $params[':s'] = '%' . $search . '%';
}
if ($dateFrom !== '') {
    $where[] = 'DATE(s.created_at) >= :df';
    $params[':df'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'DATE(s.created_at) <= :dt';
    $params[':dt'] = $dateTo;
}
if ($debtOnly === '1') {
    $where[] = 's.remaining_amount > 0';
}

$whereStr = implode(' AND ', $where);

// Satışlar listesi
$stmtList = $pdo->prepare("
    SELECT s.*, 
        CONCAT(c.first_name, ' ', c.last_name) as customer_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE $whereStr
    ORDER BY s.created_at DESC
");
$stmtList->execute($params);
$sales = $stmtList->fetchAll();

// Günlük İstatistik (Günün Satışları)
$today = date('Y-m-d');
$summary = $pdo->query("
    SELECT 
        COUNT(*) as total_sales,
        SUM(final_amount) as total_revenue,
        SUM(paid_amount) as total_paid,
        SUM(remaining_amount) as total_debt
    FROM sales 
    WHERE DATE(created_at) = '$today'
")->fetch();
$summary['total_profit'] = 0; // hesaplamak için ayrı sorgu gerekir

$pageTitle = __('sales');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>
<!-- Bugünkü Özet -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-cart-check"></i></div>
        <div>
            <div class="stat-label">
                <?= __('today_sales') ?>
            </div>
            <div class="stat-value">
                <?= (int) $summary['total_sales'] ?>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
        <div>
            <div class="stat-label">
                <?= __('today_revenue') ?>
            </div>
            <div class="stat-value" style="font-size:16px;">
                <?= formatMoney((float) $summary['total_revenue']) ?>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-graph-up-arrow"></i></div>
        <div>
            <div class="stat-label">
                <?= __('today_profit') ?>
            </div>
            <div class="stat-value" style="font-size:16px;">
                <?= formatMoney((float) $summary['total_profit']) ?>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-exclamation-circle"></i></div>
        <div>
            <div class="stat-label">
                <?= __('today_unpaid') ?>
            </div>
            <div class="stat-value" style="font-size:16px;">
                <?= formatMoney((float) $summary['total_debt']) ?>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label-dark">
                    <?= __('search_customer_or_receipt') ?>
                </label>
                <input type="text" name="search" class="form-control-dark"
                    placeholder="<?= __('search_customer_or_receipt') ?>" value="<?= e($search) ?>">
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
            <div class="col-md-2">
                <label class="form-label-dark">
                    <?= __('status') ?>
                </label>
                <select name="debt_only" class="form-select-dark">
                    <option value="" <?= $debtOnly !== '1' ? 'selected' : '' ?>>
                        <?= __('all') ?>
                    </option>
                    <option value="1" <?= $debtOnly === '1' ? 'selected' : '' ?>>
                        <?= __('has_debt') ?>
                    </option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn-accent"><i class="bi bi-search"></i>
                    <?= __('search') ?>
                </button>
                <a href="index.php" class="btn btn-outline-secondary btn-sm align-self-end">
                    <?= __('all') ?>
                </a>
                <a href="new.php" class="btn-accent ms-auto">
                    <i class="bi bi-plus-lg"></i>
                    <?= __('new_sale') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tablo -->
<div class="panel">
    <div class="panel-header">
        <h5><i class="bi bi-receipt me-2"></i>
            <?= __('sales_list') ?><span class="badge bg-secondary ms-2">
                <?= count($sales) ?>
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
                        <?= __('customer') ?>
                    </th>
                    <th>
                        <?= __('total') ?>
                    </th>
                    <th>
                        <?= __('discount') ?>
                    </th>
                    <th>
                        <?= __('net_total') ?>
                    </th>
                    <th>
                        <?= __('paid') ?>
                    </th>
                    <th>
                        <?= __('debt') ?>
                    </th>
                    <th>
                        <?= __('actions') ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5" style="color:var(--text-muted);">
                            <i class="bi bi-receipt" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                            <?= __('no_data') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sales as $s): ?>
                        <tr class="<?= $s['remaining_amount'] > 0 ? 'row-low' : '' ?>">
                            <td style="color:var(--text-muted);font-size:12px;">#
                                <?= $s['id'] ?>
                            </td>
                            <td style="font-size:12px;">
                                <?= date('d.m.Y H:i', strtotime($s['created_at'])) ?>
                            </td>
                            <td>
                                <?php if ($s['customer_name']): ?>
                                    <a href="<?= BASE_URL ?>/modules/customers/detail.php?id=<?= $s['customer_id'] ?>"
                                        style="color:var(--accent);text-decoration:none;font-weight:600;">
                                        <?= e($s['customer_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">
                                        <?= __('cash_customer') ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= formatMoney((float) $s['total_amount']) ?>
                            </td>
                            <td style="color:var(--warning);">
                                <?php if ($s['discount_value'] > 0): ?>
                                    <?= $s['discount_type'] === 'percent'
                                        ? '%' . $s['discount_value']
                                        : formatMoney((float) $s['discount_value']) ?>
                                <?php else: ?>—
                                <?php endif; ?>
                            </td>
                            <td><strong>
                                    <?= formatMoney((float) $s['final_amount']) ?>
                                </strong></td>
                            <td style="color:var(--success);">
                                <?= formatMoney((float) $s['paid_amount']) ?>
                            </td>
                            <td>
                                <?php if ($s['remaining_amount'] > 0): ?>
                                    <span class="badge-stock-out">
                                        <?= formatMoney((float) $s['remaining_amount']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge-stock-ok">
                                        <?= __('paid') ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="invoice.php?id=<?= $s['id'] ?>" target="_blank" class="btn-sm-icon"
                                        title="<?= __('print') ?>">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="delete.php?id=<?= $s['id'] ?>" class="btn-sm-icon btn-delete"
                                        data-confirm="<?= sprintf(__('cancel_sale_confirm'), $s['id']) ?>"
                                        title="<?= __('delete') ?>">
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