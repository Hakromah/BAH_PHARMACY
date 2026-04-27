<?php
/**
 * Reports Home
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();

// ── Tarih aralığı ──────────────────────────────────────
$period = get('period', 'month'); // today | week | month | custom
$dateFrom = get('date_from');
$dateTo = get('date_to');

switch ($period) {
    case 'today':
        $dateFrom = $dateTo = date('Y-m-d');
        break;
    case 'week':
        $dateFrom = date('Y-m-d', strtotime('monday this week'));
        $dateTo = date('Y-m-d');
        break;
    case 'month':
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-d');
        break;
    case 'year':
        $dateFrom = date('Y-01-01');
        $dateTo = date('Y-m-d');
        break;
    // custom: dateFrom/dateTo GET'ten gelir
}

$dateFrom = $dateFrom ?: date('Y-m-01');
$dateTo = $dateTo ?: date('Y-m-d');

$rangeParams = [':df' => $dateFrom, ':dt' => $dateTo];

// Özet İstatistikler (sales tablosu)
$stmt = $pdo->prepare("
    SELECT 
        COUNT(id)                AS sale_count,
        SUM(final_amount)        AS net_revenue,
        SUM(paid_amount)         AS collected,
        SUM(remaining_amount)    AS uncollected
    FROM sales
    WHERE DATE(created_at) >= :df AND DATE(created_at) <= :dt
");
$stmt->execute($rangeParams);
$overview = $stmt->fetch();

// Maliyet verisi
$stmtCost = $pdo->prepare("
    SELECT SUM(p.purchase_price * si.quantity) as total_cost
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    JOIN products p ON si.product_id = p.id
    WHERE DATE(s.created_at) >= :df AND DATE(s.created_at) <= :dt
");
$stmtCost->execute($rangeParams);
$costData = $stmtCost->fetch();

$revenue = (float) ($overview['net_revenue'] ?? 0);
$cost    = (float) ($costData['total_cost'] ?? 0);
$profit  = $revenue - $cost;
$margin  = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;

// Günlük satış grafiği
$stmtDaily = $pdo->prepare("
    SELECT DATE(created_at) as date, SUM(final_amount) as revenue, COUNT(id) as counts
    FROM sales
    WHERE DATE(created_at) >= :df AND DATE(created_at) <= :dt
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmtDaily->execute($rangeParams);
$dailySales = $stmtDaily->fetchAll();

// En çok satan ürünler
$stmtTopProd = $pdo->prepare("
    SELECT p.name, p.unit,
           SUM(si.quantity) as total_qty,
           SUM(si.total_price) as total_revenue,
           SUM((si.unit_price - p.purchase_price) * si.quantity) as profit
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    JOIN products p ON si.product_id = p.id
    WHERE DATE(s.created_at) >= :df AND DATE(s.created_at) <= :dt
    GROUP BY p.id
    ORDER BY total_qty DESC
    LIMIT 10
");
$stmtTopProd->execute($rangeParams);
$topProducts = $stmtTopProd->fetchAll();

// Kategori dağılımı
$stmtCat = $pdo->prepare("
    SELECT c.name as cat_name,
           SUM(si.quantity) as total_qty,
           SUM(si.total_price) as total_revenue
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    JOIN products p ON si.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    WHERE DATE(s.created_at) >= :df AND DATE(s.created_at) <= :dt
    GROUP BY c.id
    ORDER BY total_revenue DESC
    LIMIT 5
");
$stmtCat->execute($rangeParams);
$catSales = $stmtCat->fetchAll();

// En iyi müşteriler
$stmtCust = $pdo->prepare("
    SELECT CONCAT(c.first_name, ' ', c.last_name) as customer_name,
           COUNT(s.id) as sale_count,
           SUM(s.final_amount) as total_spent,
           c.total_debt
    FROM sales s
    JOIN customers c ON s.customer_id = c.id
    WHERE DATE(s.created_at) >= :df AND DATE(s.created_at) <= :dt
    GROUP BY c.id
    ORDER BY total_spent DESC
    LIMIT 5
");
$stmtCust->execute($rangeParams);
$topCustomers = $stmtCust->fetchAll();

// Chart verileri
$chartLabels  = json_encode(array_map(fn($d) => date('d.m', strtotime($d['date'])), $dailySales));
$chartRevenue = json_encode(array_column($dailySales, 'revenue'));
$chartCount   = json_encode(array_column($dailySales, 'counts'));

$pageTitle = __('reports');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>
<!-- Dönem Filtresi -->
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-auto">
                <label class="form-label-dark"><?= __('quick_period') ?></label>
                <div class="d-flex gap-2 flex-wrap">
                    <?php foreach (['today' => __('today'), 'week' => __('this_week'), 'month' => __('this_month'), 'year' => __('this_year'), 'custom' => __('custom')] as $k => $v): ?>
                        <a href="?period=<?= $k ?>" class="btn btn-sm <?= $period === $k ? 'btn-primary' : 'btn-outline-secondary' ?>">
                            <?= $v ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($period === 'custom'): ?>
                <div class="col-md-2">
                    <label class="form-label-dark"><?= __('start_date') ?></label>
                    <input type="date" name="date_from" class="form-control-dark" value="<?= e($dateFrom) ?>">
                    <input type="hidden" name="period" value="custom">
                </div>
                <div class="col-md-2">
                    <label class="form-label-dark"><?= __('end_date') ?></label>
                    <input type="date" name="date_to" class="form-control-dark" value="<?= e($dateTo) ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn-accent"><?= __('apply') ?></button>
                </div>
            <?php endif; ?>

            <!-- Dışa Aktar -->
            <div class="col-auto ms-auto">
                <label class="form-label-dark"><?= __('export') ?></label>
                <div class="d-flex gap-2">
                    <a href="export.php?type=sales&df=<?= urlencode($dateFrom) ?>&dt=<?= urlencode($dateTo) ?>" class="btn btn-accent btn-sm">
                        <i class="bi bi-filetype-csv me-1"></i><?= __('export_csv') ?>
                    </a>
                    <button onclick="window.print()" type="button" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-print me-1"></i><?= __('print') ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Dönem Başlığı -->
<div class="mb-3" style="font-size:14px;color:var(--text-muted);">
    <i class="bi bi-calendar-range me-1"></i><?= __('period') ?> <strong style="color:var(--text-primary);">
        <?= date('d.m.Y', strtotime($dateFrom)) ?> —
        <?= date('d.m.Y', strtotime($dateTo)) ?>
    </strong>
</div>

<!-- Özet Kartlar -->
<div class="stat-cards mb-4">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-cart-check"></i></div>
        <div>
            <div class="stat-label"><?= __('sale_count') ?></div>
            <div class="stat-value"><?= (int) $overview['sale_count'] ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
        <div>
            <div class="stat-label"><?= __('net_revenue') ?></div>
            <div class="stat-value" style="font-size:15px;"><?= formatMoney($revenue) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-graph-up-arrow"></i></div>
        <div>
            <div class="stat-label"><?= __('profit') ?></div>
            <div class="stat-value" style="font-size:15px;" title="<?= __('margin') ?>: %<?= $margin ?>">
                <?= formatMoney($profit) ?>
                <small style="font-size:11px;color:var(--success);">%<?= $margin ?></small>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-box2"></i></div>
        <div>
            <div class="stat-label"><?= __('sold_cost') ?></div>
            <div class="stat-value" style="font-size:15px;"><?= formatMoney($cost) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-cash-coin"></i></div>
        <div>
            <div class="stat-label"><?= __('collected') ?></div>
            <div class="stat-value" style="font-size:15px;"><?= formatMoney((float) $overview['collected']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-exclamation-circle"></i></div>
        <div>
            <div class="stat-label"><?= __('uncollected') ?></div>
            <div class="stat-value" style="font-size:15px;"><?= formatMoney((float) $overview['uncollected']) ?></div>
        </div>
    </div>
</div>

<!-- Günlük Satış Grafiği -->
<?php if (!empty($dailySales)): ?>
    <div class="panel mb-4">
        <div class="panel-header">
            <h5><i class="bi bi-bar-chart me-2"></i><?= __('daily_chart') ?></h5>
        </div>
        <div class="panel-body" style="height:260px;"><canvas id="salesChart"></canvas></div>
    </div>
<?php endif; ?>

<div class="row g-4">

    <!-- En Çok Satan Ürünler -->
    <div class="col-md-7">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-trophy me-2"></i><?= __('top_products') ?></h5>
                <a href="export.php?type=products&df=<?= urlencode($dateFrom) ?>&dt=<?= urlencode($dateTo) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-filetype-csv me-1"></i><?= __('export_csv') ?>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table-dark-custom">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= __('product') ?></th>
                            <th><?= __('sold') ?></th>
                            <th><?= __('revenue') ?></th>
                            <th><?= __('profit') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topProducts)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted"><?= __('no_data') ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($topProducts as $i => $p): ?>
                                <tr>
                                    <td><?php if ($i === 0): ?>🥇<?php elseif ($i === 1): ?>🥈<?php elseif ($i === 2): ?>🥉<?php else: ?><?= $i + 1 ?><?php endif; ?></td>
                                    <td><strong><?= e($p['name']) ?></strong></td>
                                    <td>
                                        <strong style="color:var(--accent);"><?= (int) $p['total_qty'] ?></strong>
                                        <span class="text-muted" style="font-size:11px;"><?= e($p['unit']) ?></span>
                                    </td>
                                    <td><?= formatMoney((float) $p['total_revenue']) ?></td>
                                    <td style="color:<?= $p['profit'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                                        <?= formatMoney((float) $p['profit']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sağ Kolon -->
    <div class="col-md-5">

        <!-- Kategori Dağılımı -->
        <div class="panel mb-4">
            <div class="panel-header">
                <h5><i class="bi bi-pie-chart me-2"></i><?= __('category_dist') ?></h5>
            </div>
            <div class="table-responsive">
                <table class="table-dark-custom">
                    <thead>
                        <tr>
                            <th><?= __('category') ?></th>
                            <th><?= __('sold') ?></th>
                            <th><?= __('revenue') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($catSales)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted"><?= __('no_data') ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($catSales as $c): ?>
                                <tr>
                                    <td><strong><?= e($c['cat_name']) ?></strong></td>
                                    <td><?= (int) $c['total_qty'] ?></td>
                                    <td><?= formatMoney((float) $c['total_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- En İyi Müşteriler -->
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-person-check me-2"></i><?= __('top_customers') ?></h5>
                <a href="export.php?type=customers&df=<?= urlencode($dateFrom) ?>&dt=<?= urlencode($dateTo) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-filetype-csv me-1"></i><?= __('export_csv') ?>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table-dark-custom">
                    <thead>
                        <tr>
                            <th><?= __('customer') ?></th>
                            <th><?= __('sale_count') ?></th>
                            <th><?= __('total_spent') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topCustomers)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted"><?= __('no_data') ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($topCustomers as $c): ?>
                                <tr>
                                    <td>
                                        <strong style="font-size:13px;"><?= e($c['customer_name']) ?></strong>
                                        <?php if ($c['total_debt'] > 0): ?>
                                            <div style="font-size:11px;color:var(--danger);"><?= __('debt') ?> <?= formatMoney((float) $c['total_debt']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int) $c['sale_count'] ?></td>
                                    <td><?= formatMoney((float) $c['total_spent']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Chart.js CDN & Grafik -->
<?php if (!empty($dailySales)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        Chart.defaults.color = 'rgba(255, 255, 255, 0.6)';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';
        const labels = <?= $chartLabels ?>;
        const revenueData = <?= $chartRevenue ?>;
        const countsData = <?= $chartCount ?>;

        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '<?= __('revenue') ?>',
                        data: revenueData,
                        backgroundColor: 'rgba(14, 165, 233, 0.8)',
                    },
                    {
                        label: '<?= __('transaction') ?>',
                        data: countsData,
                        type: 'line',
                        borderColor: '#f59e0b',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: { type: 'linear', display: true, position: 'left' },
                    y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false } }
                }
            }
        });
    </script>
<?php endif; ?>

<style>
@media print {
    body { background: white; color: black; }
    .sidebar, .topbar, .btn { display: none !important; }
}
</style>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>