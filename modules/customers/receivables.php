<?php
/**
 * Receivables Schedule
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();

// ── Veri Çekme ───────────────────────────────────────
// Kalan borcu olan satışları vade tarihine göre çek
$stmt = $pdo->prepare("
    SELECT s.*, 
           c.first_name, c.last_name, c.phone, c.unique_id,
           DATEDIFF(s.due_date, CURDATE()) as days_left
    FROM sales s
    JOIN customers c ON s.customer_id = c.id
    WHERE s.remaining_amount > 0
    ORDER BY s.due_date ASC, s.created_at ASC
");
$stmt->execute();
$receivables = $stmt->fetchAll();

// Özet İstatistikler
$today = date('Y-m-d');
$overdueTotal = 0;
$upcomingTotal = 0;
$targetCurrency = getCurrentCurrency();

foreach ($receivables as $r) {
    $amt = convertCurrency((float) $r['remaining_amount'], $r['currency'], $targetCurrency);
    if ($r['due_date'] < $today) {
        $overdueTotal += $amt;
    } else {
        $upcomingTotal += $amt;
    }
}

$pageTitle = __('receivables_list');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<div class="stat-cards mb-4">
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-exclamation-triangle"></i></div>
        <div>
            <div class="stat-label"><?= __('overdue_total') ?></div>
            <div class="stat-value text-danger">
                <?= formatMoney($overdueTotal) ?>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-clock-history"></i></div>
        <div>
            <div class="stat-label"><?= __('upcoming_receivables') ?></div>
            <div class="stat-value text-warning">
                <?= formatMoney($upcomingTotal) ?>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-clipboard-data"></i></div>
        <div>
            <div class="stat-label"><?= __('total_pending') ?></div>
            <div class="stat-value">
                <?= formatMoney($overdueTotal + $upcomingTotal) ?>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header d-flex justify-content-between align-items-center">
        <h5><i class="bi bi-calendar3 me-2"></i><?= __('payment_schedule') ?></h5>
        <div class="d-flex gap-2">
            <span class="badge bg-danger"><?= __('overdue') ?></span>
            <span class="badge bg-warning text-dark"><?= __('today_upcoming') ?></span>
            <span class="badge bg-success"><?= __('future') ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-dark-custom">
            <thead>
                <tr>
                    <th><?= __('customer') ?></th>
                    <th><?= __('sale_no_batch_date') ?></th>
                    <th><?= __('due_date') ?></th>
                    <th><?= __('status') ?></th>
                    <th class="text-end"><?= __('remaining_amount') ?></th>
                    <th class="text-end"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($receivables)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-check2-circle mb-2" style="font-size:3rem; display:block;"></i>
                            <?= __('no_pending_receivables_found') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($receivables as $r): ?>
                        <?php
                        $statusClass = '';
                        $statusText = '';
                        $days = (int) $r['days_left'];

                        if ($days < 0) {
                            $statusClass = 'text-danger fw-bold';
                            $statusText = abs($days) . " " . __('days_overdue');
                            $rowClass = 'row-low'; // Kırmızımsı arka plan
                        } elseif ($days == 0) {
                            $statusClass = 'text-warning fw-bold';
                            $statusText = __('due_today');
                            $rowClass = 'bg-warning-subtle';
                        } elseif ($days <= 7) {
                            $statusClass = 'text-warning';
                            $statusText = $days . " " . __('days_left');
                            $rowClass = '';
                        } else {
                            $statusClass = 'text-success';
                            $statusText = $days . " " . __('days_left');
                            $rowClass = '';
                        }
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td>
                                <a href="detail.php?id=<?= $r['customer_id'] ?>" class="fw-bold text-decoration-none"
                                    style="color:var(--text-primary)">
                                    <?= e($r['first_name'] . ' ' . $r['last_name']) ?>
                                </a>
                                <div class="small text-muted">
                                    <?= e($r['phone']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-dark">#
                                    <?= $r['id'] ?>
                                </span>
                                <div class="small text-muted">
                                    <?= date('d.m.Y', strtotime($r['created_at'])) ?>
                                </div>
                            </td>
                            <td>
                                <div class="<?= $statusClass ?>">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <?= $r['due_date'] ? date('d.m.Y', strtotime($r['due_date'])) : __('not_specified') ?>
                                </div>
                            </td>
                            <td>
                                <span class="<?= $statusClass ?> small">
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold">
                                <?= formatMoney((float) $r['remaining_amount'], $r['currency']) ?>
                            </td>
                            <td class="text-end">
                                <a href="fast_payment.php?customer_id=<?= $r['customer_id'] ?>&sale_id=<?= $r['id'] ?>"
                                    class="btn btn-sm btn-success" title="<?= __('make_payment') ?>">
                                    <i class="bi bi-cash-coin me-1"></i><?= __('make_payment') ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .bg-warning-subtle {
        background: rgba(255, 193, 7, 0.05) !important;
    }

    .row-low {
        background: rgba(239, 68, 68, 0.05) !important;
    }
</style>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>