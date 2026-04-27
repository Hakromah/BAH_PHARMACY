<?php
/**
 * Dashboard
 */
require_once dirname(__DIR__) . '/core/bootstrap.php';
$pdo = Database::getInstance();
$sym = getCurrencySymbol();

$cur = getCurrentCurrency();



// Stats queries
$todayDate = date('Y-m-d');
$todayCount = Database::getInstance()->query("SELECT COUNT(*) FROM sales WHERE DATE(created_at) = '$todayDate'")->fetchColumn();
$todayRev = sumConverted("SELECT final_amount as val, currency FROM sales WHERE DATE(created_at) = '$todayDate'", 'val', 'currency');

$monthDate = date('Y-m');
$monthCount = Database::getInstance()->query("SELECT COUNT(*) FROM sales WHERE DATE_FORMAT(created_at, '%Y-%m') = '$monthDate'")->fetchColumn();
$monthRev = sumConverted("SELECT final_amount as val, currency FROM sales WHERE DATE_FORMAT(created_at, '%Y-%m') = '$monthDate'", 'val', 'currency');

$todayProfit = sumConverted("SELECT (s.final_amount - COALESCE((SELECT SUM(si.quantity * p.purchase_price) FROM sale_items si JOIN products p ON p.id = si.product_id WHERE si.sale_id = s.id),0)) as val, s.currency FROM sales s WHERE DATE(s.created_at) = '$todayDate'", 'val', 'currency');

$monthProfit = sumConverted("SELECT (s.final_amount - COALESCE((SELECT SUM(si.quantity * p.purchase_price) FROM sale_items si JOIN products p ON p.id = si.product_id WHERE si.sale_id = s.id),0)) as val, s.currency FROM sales s WHERE DATE_FORMAT(s.created_at, '%Y-%m') = '$monthDate'", 'val', 'currency');

$totalProducts = Database::getInstance()->query("SELECT COUNT(*) FROM products")->fetchColumn();
$criticalStock = Database::getInstance()->query("SELECT COUNT(*) FROM products WHERE stock_quantity > 0 AND stock_quantity <= critical_stock")->fetchColumn();
$outOfStock = Database::getInstance()->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 0")->fetchColumn();

$totalCustomers = Database::getInstance()->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$totalDebt = sumConverted("SELECT total_debt as val, currency FROM customers WHERE total_debt > 0", 'val', 'currency');

$recentSales = Database::getInstance()->query("SELECT s.*, c.first_name, c.last_name, CONCAT(c.first_name, ' ', c.last_name) as cname FROM sales s LEFT JOIN customers c ON c.id = s.customer_id ORDER BY s.id DESC LIMIT 5")->fetchAll();

$topProducts = Database::getInstance()->query("SELECT p.name, SUM(si.quantity) as qty, SUM(si.total_price) as rev FROM sale_items si JOIN products p ON p.id = si.product_id GROUP BY p.id ORDER BY qty DESC LIMIT 5")->fetchAll();

$lowStock = Database::getInstance()->query("SELECT name, stock_quantity, critical_stock, unit FROM products WHERE stock_quantity <= critical_stock ORDER BY stock_quantity ASC LIMIT 8")->fetchAll();

// Chart 7-day
$chart7Labels = [];
$chart7Rev = [];
$chart7Count = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart7Labels[] = date('d.m', strtotime($d));
    $chart7Rev[] = sumConverted("SELECT final_amount as val, currency FROM sales WHERE DATE(created_at) = '$d'");
    $chart7Count[] = Database::getInstance()->query("SELECT COUNT(*) FROM sales WHERE DATE(created_at) = '$d'")->fetchColumn();
}
$chart7Labels = json_encode($chart7Labels);
$chart7Rev = json_encode($chart7Rev);
$chart7Count = json_encode($chart7Count);

// Category Dist
$catRows = Database::getInstance()->query("SELECT c.name, COUNT(p.id) as cnt FROM categories c JOIN products p ON p.category_id = c.id GROUP BY c.id ORDER BY cnt DESC LIMIT 5")->fetchAll();
$catLabels = [];
$catValues = [];
foreach ($catRows as $cr) {
    $catLabels[] = $cr['name'];
    $catValues[] = $cr['cnt'];
}
$catLabels = json_encode($catLabels);
$catValues = json_encode($catValues);

// Chart 6-months
$chart6mLabels = [];
$chart6mRev = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $chart6mLabels[] = date('M', strtotime("$m-01"));
    $chart6mRev[] = sumConverted("SELECT final_amount as val, currency FROM sales WHERE DATE_FORMAT(created_at, '%Y-%m') = '$m'");
}
$chart6mLabels = json_encode($chart6mLabels);
$chart6mRev = json_encode($chart6mRev);

$pageTitle = __('dashboard');
require_once dirname(__DIR__) . '/core/layout_header.php';
?>

<!-- Stat Cards -->
<div class="stat-cards mb-4">
    <div class="stat-card" onclick="window.location.href='<?= BASE_URL ?>/modules/sales/index.php?date=today'"
        style="cursor:pointer;" title="Bugünün Satışlarını Göster">
        <div class="stat-icon blue"><i class="bi bi-cart-check"></i></div>
        <div>
            <div class="stat-label"><?= __('today_sales') ?></div>
            <div class="stat-value"><?= (int) $todayCount ?></div>
            <div style="font-size:12px;color:var(--success);"><?= formatMoney((float) $todayRev) ?></div>
        </div>
    </div>
    <div class="stat-card" onclick="window.location.href='<?= BASE_URL ?>/modules/reports/index.php'"
        style="cursor:pointer;" title="Raporlara Git">
        <div class="stat-icon green"><i class="bi bi-graph-up-arrow"></i></div>
        <div>
            <div class="stat-label"><?= __('today_profit') ?></div>
            <div class="stat-value" style="font-size:16px;"><?= formatMoney((float) $todayProfit) ?></div>
        </div>
    </div>
    <div class="stat-card" onclick="window.location.href='<?= BASE_URL ?>/modules/sales/index.php'"
        style="cursor:pointer;" title="Aylık Satışları Göster">
        <div class="stat-icon blue"><i class="bi bi-cash-stack"></i></div>
        <div>
            <div class="stat-label"><?= __('monthly_revenue') ?></div>
            <div class="stat-value" style="font-size:15px;"><?= formatMoney((float) $monthRev) ?></div>
            <div style="font-size:12px;color:var(--text-muted);"><?= (int) $monthCount ?> <?= __('sale_count') ?></div>
        </div>
    </div>
    <div class="stat-card" onclick="window.location.href='<?= BASE_URL ?>/modules/reports/index.php'"
        style="cursor:pointer;" title="Raporlara Git">
        <div class="stat-icon green"><i class="bi bi-trophy"></i></div>
        <div>
            <div class="stat-label"><?= __('monthly_profit') ?></div>
            <div class="stat-value" style="font-size:15px;"><?= formatMoney((float) $monthProfit) ?></div>
        </div>
    </div>
    <div class="stat-card" onclick="window.location.href='<?= BASE_URL ?>/modules/products/index.php'"
        style="cursor:pointer;" title="Ürün Listesine Git">
        <div class="stat-icon orange"><i class="bi bi-box-seam"></i></div>
        <div>
            <div class="stat-label"><?= __('total_products') ?></div>
            <div class="stat-value"><?= $totalProducts ?></div>
            <?php if ($criticalStock > 0): ?>
                <div style="font-size:12px;color:var(--warning);">⚠ <?= $criticalStock ?>     <?= __('critical') ?></div>
            <?php endif; ?>
            <?php if ($outOfStock > 0): ?>
                <div style="font-size:12px;color:var(--danger);">❌ <?= $outOfStock ?>     <?= __('out_of_stock') ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card" onclick="window.location.href='<?= BASE_URL ?>/modules/customers/index.php'"
        style="cursor:pointer;" title="Müşteri Listesine Git">
        <div class="stat-icon blue"><i class="bi bi-people"></i></div>
        <div>
            <div class="stat-label"><?= __('total_customers') ?></div>
            <div class="stat-value"><?= $totalCustomers ?></div>
        </div>
    </div>
    <div class="stat-card" onclick="window.location.href='<?= BASE_URL ?>/modules/customers/receivables.php'"
        style="cursor:pointer;" title="Alacak Listesine Git">
        <div class="stat-icon red"><i class="bi bi-exclamation-triangle"></i></div>
        <div>
            <div class="stat-label"><?= __('total_debt') ?></div>
            <div class="stat-value" style="font-size:15px;"><?= formatMoney((float) $totalDebt) ?></div>
        </div>
    </div>
</div>

<!-- Grafik Satırı -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-bar-chart me-2"></i>
                    <?= __('sales_chart') ?>
                </h5>
            </div>
            <div class="panel-body" style="height:280px;"><canvas id="chart7"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-pie-chart me-2"></i>
                    <?= __('category_dist') ?>
                </h5>
            </div>
            <div class="panel-body" style="height:280px;"><canvas id="chartPie"></canvas></div>
        </div>
    </div>
</div>

<!-- Aylık Trend + Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-graph-up me-2"></i><?= __('six_month_trend') ?></h5>
            </div>
            <div class="panel-body" style="height:220px;"><canvas id="chart6m"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-lightning me-2"></i>
                    <?= __('quick_actions') ?>
                </h5>
            </div>
            <div class="panel-body">
                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>/modules/sales/new.php" class="btn-accent"><i
                            class="bi bi-plus-circle me-2"></i>
                        <?= __('new_sale') ?>
                    </a>
                    <a href="<?= BASE_URL ?>/modules/customers/fast_payment.php" class="btn btn-outline-success"><i
                            class="bi bi-cash-coin me-2"></i><?= __('fast_payment') ?></a>
                    <a href="<?= BASE_URL ?>/modules/stock/entry.php" class="btn btn-outline-info"><i
                            class="bi bi-box-arrow-in-down me-2"></i>
                        <?= __('stock_entry') ?>
                    </a>
                    <a href="<?= BASE_URL ?>/modules/products/form.php" class="btn btn-outline-primary"><i
                            class="bi bi-box-seam me-2"></i>
                        <?= __('new_product') ?>
                    </a>
                    <a href="<?= BASE_URL ?>/modules/customers/form.php" class="btn btn-outline-secondary"
                        style="border-color:#8b5cf6; color:#8b5cf6;"><i class="bi bi-person-plus me-2"></i>
                        <?= __('new_customer') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alt Satır: Son Satışlar + En Çok Satan + Düşük Stok -->
<div class="row g-4">
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-clock-history me-2"></i>
                    <?= __('recent_sales') ?>
                </h5>
                <a href="<?= BASE_URL ?>/modules/sales/index.php" class="btn btn-sm btn-outline-secondary">
                    <?= __('all') ?>
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
                                <?= __('customer') ?>
                            </th>
                            <th>
                                <?= __('total') ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentSales)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    <?= __('no_data') ?>
                                </td>
                            </tr>
                        <?php else:
                            foreach ($recentSales as $s): ?>
                                <tr>
                                    <td style="font-size:12px;">
                                        <?= date('d.m H:i', strtotime($s['created_at'])) ?>
                                    </td>
                                    <td style="font-size:13px;">
                                        <?= e($s['cname'] ?? __('walk_in')) ?>
                                    </td>
                                    <td>
                                        <strong>
                                            <?= formatMoney((float) $s['final_amount'], $s['currency'] ?? 'USD') ?>
                                        </strong>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-trophy me-2"></i>
                    <?= __('top_products') ?>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table-dark-custom">
                    <thead>
                        <tr>
                            <th>
                                <?= __('product_name') ?>
                            </th>
                            <th>
                                <?= __('quantity') ?>
                            </th>
                            <th>
                                <?= __('revenue') ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topProducts)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    <?= __('no_data') ?>
                                </td>
                            </tr>
                        <?php else:
                            foreach ($topProducts as $i => $p): ?>
                                <tr>
                                    <td style="font-size:13px;">
                                        <?= $i < 3 ? ['🥇', '🥈', '🥉'][$i] : ($i + 1) . '. ' ?>
                                        <?= e($p['name']) ?>
                                    </td>
                                    <td><strong style="color:var(--accent);">
                                            <?= (int) $p['qty'] ?>
                                        </strong></td>
                                    <td>
                                        <?= formatMoney((float) $p['rev']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-exclamation-triangle me-2"></i>
                    <?= __('low_stock_alert') ?>
                </h5>
            </div>
            <div class="panel-body p-0">
                <?php if (empty($lowStock)): ?>
                    <div class="text-center py-4 text-muted" style="font-size:13px;"><i class="bi bi-check-circle me-1"></i>
                        <?= __('sufficient') ?>
                    </div>
                <?php else:
                    foreach ($lowStock as $ls):
                        $isOut = $ls['stock_quantity'] <= 0; ?>
                        <div class="px-3 py-2" style="border-bottom:1px solid rgba(255,255,255,0.04);">
                            <div style="font-size:13px;font-weight:600;">
                                <?= e($ls['name']) ?>
                            </div>
                            <div style="font-size:12px;">
                                <span style="color:<?= $isOut ? 'var(--danger)' : 'var(--warning)' ?>;font-weight:700;">
                                    <?= (int) $ls['stock_quantity'] ?>
                                </span>
                                <span class="text-muted">/
                                    <?= (int) $ls['critical_stock'] ?>
                                    <?= e($ls['unit']) ?>
                                </span>
                                <?php if ($isOut): ?><span class="badge bg-danger ms-1" style="font-size:10px;">
                                        <?= __('out_of_stock') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.color = 'rgba(255, 255, 255, 0.6)';
    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';
    const chartOpts = { responsive: true, maintainAspectRatio: false };

    // 7 Günlük Satışlar
    new Chart(document.getElementById('chart7'), {
        type: 'bar',
        data: {
            labels: <?= $chart7Labels ?>,
            datasets: [{
                label: '<?= __('revenue') ?>',
                data: <?= $chart7Rev ?>,
                backgroundColor: 'rgba(14, 165, 233, 0.3)',
                borderColor: '#0ea5e9',
                borderWidth: 2,
                borderRadius: 4
            }, {
                label: '<?= __('sale_count') ?>',
                data: <?= $chart7Count ?>,
                type: 'line',
                borderColor: '#22c55e',
                borderWidth: 2,
                tension: 0.3
            }]
        },
        options: chartOpts
    });

    // Kategori Dağılımı
    new Chart(document.getElementById('chartPie'), {
        type: 'doughnut',
        data: {
            labels: <?= $catLabels ?>,
            datasets: [{
                data: <?= $catValues ?>,
                backgroundColor: ['#0ea5e9', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6'],
                borderWidth: 0
            }]
        },
        options: { ...chartOpts, cutout: '70%' }
    });

    // 6 Aylık Trend
    new Chart(document.getElementById('chart6m'), {
        type: 'line',
        data: {
            labels: <?= $chart6mLabels ?>,
            datasets: [{
                label: '<?= __('revenue') ?>',
                data: <?= $chart6mRev ?>,
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: chartOpts
    });
</script>

<?php require_once dirname(__DIR__) . '/core/layout_footer.php'; ?>

