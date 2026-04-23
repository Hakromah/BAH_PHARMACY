<?php
/**
 * Payment Receipt
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$id = (int) get('id');

if ($id <= 0) {
    setFlash('error', __('invalid_receipt'));
    redirect(BASE_URL . '/modules/customers/index.php');
}

// Ödeme
$stmt = $pdo->prepare("
    SELECT p.*, c.first_name, c.last_name, c.phone, c.address, c.unique_id, c.total_debt
    FROM payments p
    JOIN customers c ON p.customer_id = c.id
    WHERE p.id = :id
");
$stmt->execute([':id' => $id]);
$payment = $stmt->fetch();

if (!$payment) {
    die(__('receipt_not_found'));
}

$methodLabels = [
    'cash' => __('cash'),
    'card' => __('card'),
    'transfer' => __('transfer'),
    'other' => __('other')
];

$pageTitle = __('manual_debt_receipt');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';

// Şablon ve Veri Hazırlama
$tpl = getReportTemplate('receipt', $pdo);
?>

<style>
    /* Yazıcı ve Rapor Temizliği */
    @media print {

        header,
        .sidebar,
        .topbar,
        .btn-accent,
        .btn-outline-secondary,
        .nav-label,
        .sidebar-toggle,
        .d-print-none {
            display: none !important;
        }

        #content {
            margin: 0 !important;
            width: 100% !important;
            border: none !important;
            background: white !important;
            overflow: visible !important;
        }

        body,
        html {
            background: white !important;
            overflow: visible !important;
            height: auto !important;
        }

        .page-content {
            padding: 0 !important;
        }

        .panel {
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
        }
    }

    .report-paper {
        background: white;
        padding: 60px;
        color: #333;
        font-size:
            <?= (int) ($tpl['settings']['font_size'] ?? 14) . 'px' ?>
        ;
        min-height: 800px;
        line-height: 1.6;
        border: 1px solid #eee;
    }

    .report-logo {
        width:
            <?= (int) ($tpl['settings']['logo_size'] ?? 60) . 'px' ?>
        ;
        height: auto;
    }

    .section-box {
        margin-bottom: 40px;
    }

    .receipt-badge {
        background: #f0fdf4;
        border: 2px dashed #bbf7d0;
        border-radius: 12px;
        padding: 30px;
        text-align: center;
    }
</style>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4 d-print-none">
            <h4 style="margin:0;"><i class="bi bi-receipt me-2"></i><?= __('receipt') ?></h4>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/modules/settings/reports.php?type=receipt" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-pencil-square me-1"></i><?= __('edit_design') ?></a>
                <button onclick="window.print()" class="btn-accent btn-sm">
                    <i class="bi bi-printer me-1"></i><?= __('print') ?> / PDF</button>
                <a href="detail.php?id=<?= $payment['customer_id'] ?>" class="btn btn-outline-secondary btn-sm"><i
                        class="bi bi-arrow-left me-1"></i><?= __('back') ?></a>
            </div>
        </div>


        <div class="panel report-paper shadow-sm">
            <?php
            $sections = $tpl['sections'];
            usort($sections, function ($a, $b) {
                return ($a['order'] ?? 0) - ($b['order'] ?? 0);
            });

            foreach ($sections as $sec):
                if (!($sec['visible'] ?? true))
                    continue;
                echo '<div class="section-box" id="section-' . $sec['id'] . '">';

                switch ($sec['id']) {
                    case 'logo_header': ?>
                        <div class="d-flex justify-content-between align-items-start border-bottom pb-4">
                            <div>
                                <?php if (file_exists(dirname(__DIR__, 2) . '/storage/images/logo.png')): ?>
                                    <img src="<?= BASE_URL ?>/storage/images/logo.png" class="report-logo">
                                <?php else: ?>
                                    <h4 class="fw-bold m-0">BAH PHARMACY</h4>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <h2 class="fw-bold m-0 text-success"><?= mb_strtoupper(__('manual_debt_receipt'), 'UTF-8') ?></h2>
                                <div class="fw-bold">No: #<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></div>
                                <div class="text-muted small"><?= date('d.m.Y H:i', strtotime($payment['created_at'])) ?></div>
                            </div>
                        </div>
                        <?php break;

                    case 'customer_meta': ?>
                        <div class="row">
                            <div class="col-12">
                                <h6 class="fw-bold opacity-75 text-uppercase mb-3" style="font-size:11px; letter-spacing:1px;">
                                    <?= mb_strtoupper(__('customer_info'), 'UTF-8') ?>
                                </h6>
                                <div class="fs-4 fw-bold mb-1"><?= e($payment['first_name'] . ' ' . $payment['last_name']) ?></div>
                                <div class="text-muted">ID: <?= e($payment['unique_id']) ?> | <?= e($payment['phone'] ?: '—') ?>
                                </div>
                                <div class="text-muted small"><?= e($payment['address'] ?: '—') ?></div>
                            </div>
                        </div>
                        <?php break;

                    case 'summary_bar': ?>
                        <div class="receipt-badge">
                            <div class="text-muted text-uppercase mb-1" style="font-size:12px; font-weight:600;">
                                <?= __('payment_amount') ?></div>
                            <div class="display-4 fw-bold text-success"><?= formatMoney((float) $payment['amount']) ?></div>
                        </div>
                        <?php break;

                    case 'main_table': ?>
                        <div class="mt-4">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:30%"><?= __('description') ?></th>
                                        <th style="width:25%"><?= __('payment_method') ?></th>
                                        <th class="text-end"><?= __('amount') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?= e($payment['note'] ?: __('manual_debt_receipt')) ?></td>
                                        <td><strong><?= $methodLabels[$payment['method']] ?? __('other') ?></strong></td>
                                        <td class="text-end fw-bold fs-5"><?= formatMoney((float) $payment['amount']) ?></td>
                                    </tr>
                                    <?php if ($payment['total_debt'] != 0): ?>
                                        <tr class="table-light">
                                            <td colspan="2" class="text-end text-muted small text-uppercase fw-bold">
                                                <?= __('remaining_debt') ?>:</td>
                                            <td class="text-end fw-bold"><?= formatMoney((float) $payment['total_debt']) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php break;

                    case 'notes_footer': ?>
                        <div class="mt-5 d-flex justify-content-between text-center">
                            <div style="width:200px; border-top:1px solid #333; padding-top:10px;">
                                <div class="small fw-bold"><?= mb_strtoupper(__('delivered_by'), 'UTF-8') ?></div>
                                <div class="text-muted small"><?= __('pharmacist') ?></div>
                            </div>
                            <div style="width:200px; border-top:1px solid #333; padding-top:10px;">
                                <div class="small fw-bold"><?= mb_strtoupper(__('delivered_to'), 'UTF-8') ?></div>
                                <div class="text-muted small"><?= __('signature') ?></div>
                            </div>
                        </div>
                        <div class="mt-5 pt-4 text-center text-muted" style="font-size:0.8em; border-top:1px solid #eee;">
                            <p><?= __('non_financial_doc') ?></p>
                        </div>
                        <?php break;
                }
                echo '</div>';
            endforeach; ?>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>