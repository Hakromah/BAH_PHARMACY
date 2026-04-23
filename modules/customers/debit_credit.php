<?php
require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$id = (int) get('id'); // Payment ID veya Sale ID olabilir, biz burada payment üzerinden gidelim (dekont ödemedir)

if ($id <= 0) {
    die("Geçersiz işlem.");
}

$stmt = $pdo->prepare("
    SELECT p.*, c.first_name, c.last_name, c.phone, c.address, c.unique_id, c.total_debt
    FROM payments p
    JOIN customers c ON p.customer_id = c.id
    WHERE p.id = :id
");
$stmt->execute([':id' => $id]);
$payment = $stmt->fetch();

if (!$payment) {
    die("Dekont bulunamadı.");
}

$pageTitle = 'Borç/Alacak Dekontu';
require_once dirname(__DIR__, 2) . '/core/layout_header.php';

// Şablon ve Veri Hazırlama
$tpl = getReportTemplate('debit_credit', $pdo);
?>

<style>
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
        padding: 50px;
        color: #333;
        font-size:
            <?= (int) ($tpl['settings']['font_size'] ?? 14) ?>
            px;
        min-height: 1000px;
        line-height: 1.5;
    }

    .report-logo {
        width:
            <?= (int) ($tpl['settings']['logo_size'] ?? 60) ?>
            px;
        height: auto;
    }

    .section-box {
        margin-bottom: 35px;
    }

    .debit-box {
        border: 2px solid #333;
        padding: 20px;
        position: relative;
    }

    .debit-title {
        position: absolute;
        top: -12px;
        left: 20px;
        background: white;
        padding: 0 10px;
        font-weight: 800;
        font-size: 14px;
    }
</style>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Kontroller -->
        <div class="d-flex align-items-center justify-content-between mb-4 d-print-none">
            <h4 style="margin:0;"><i class="bi bi-file-earmark-check me-2"></i>Receipt</h4>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/modules/settings/reports.php?type=debit_credit"
                    class="btn btn-outline-info btn-sm">
                    <i class="bi bi-pencil-square me-1"></i>Edit Design</a>
                <button onclick="window.print()" class="btn-accent btn-sm">
                    <i class="bi bi-printer me-1"></i>Print / PDF</button>
                <a href="detail.php?id=<?= $payment['customer_id'] ?>" class="btn btn-outline-secondary btn-sm">Return
                    to Customer</a>
            </div>
        </div>

        <div class="panel report-paper shadow-sm">
            <?php
            $sections = $tpl['sections'];
            usort($sections, function ($a, $b) {
                return ($a['order'] ?? 0) - ($b['order'] ?? 0); });

            foreach ($sections as $sec):
                if (!($sec['visible'] ?? true))
                    continue;
                echo '<div class="section-box" id="section-' . $sec['id'] . '">';

                switch ($sec['id']) {
                    case 'logo_header': ?>
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-4">
                            <?php if (file_exists(dirname(__DIR__, 2) . '/storage/images/logo.png')): ?>
                                <img src="<?= BASE_URL ?>/storage/images/logo.png" class="report-logo">
                            <?php else: ?>
                                <h4 class="fw-bold m-0">BAH PHARMACY</h4>
                            <?php endif; ?>
                            <div class="text-end">
                                <h2 class="fw-bold m-0">PAYMENT RECEIPT</h2>
                                <div class="text-muted small"><?= date('d.m.Y H:i', strtotime($payment['created_at'])) ?></div>
                            </div>
                        </div>
                        <?php break;

                    case 'customer_meta': ?>
                        <div class="debit-box mt-4">
                            <span class="debit-title">Related Account</span>
                            <div class="row">
                                <div class="col-6">
                                    <div class="fw-bold fs-5"><?= e($payment['first_name'] . ' ' . $payment['last_name']) ?></div>
                                    <div class="text-muted small">Account No: <?= e($payment['unique_id']) ?></div>
                                </div>
                                <div class="col-6 text-end">
                                    <div>Date: <strong><?= date('d.m.Y', strtotime($payment['created_at'])) ?></strong></div>
                                    <div>Reference: <strong>#<?= $id ?></strong></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php break;

                    case 'summary_bar': ?>
                    <div class="mt-4 p-4 border rounded-3 bg-light">
                        <div class="row align-items-center">
                            <div class="col-7">
                                <div class="text-muted small text-uppercase fw-bold">TRANSACTION AMOUNT</div>
                                <div class="display-6 fw-bold"><?= formatMoney((float) $payment['amount']) ?></div>
                            </div>
                            <div class="col-5 text-end">
                                <div class="badge bg-dark fs-6 px-3 py-2">
                                    <?= $payment['amount'] > 0 ? 'CREDIT ENTRY' : 'DEBIT ENTRY' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php break;

                    case 'main_table': ?>
                    <div class="mt-4">
                        <h6 class="fw-bold border-bottom pb-2">TRANSACTION DETAILS</h6>
                        <table class="table table-borderless">
                            <tr>
                                <th style="width:150px;">Transaction Type:</th>
                                <td>Balance Adjustment / Receipt</td>
                            </tr>
                            <tr>
                                <th>Description:</th>
                                <td><?= e($payment['note'] ?: 'No description provided.') ?></td>
                            </tr>
                            <tr>
                                <th>Cash/Bank:</th>
                                <td>Virtual Cash</td>
                            </tr>
                        </table>
                    </div>
                    <?php break;

                    case 'notes_footer': ?>
                    <div class="mt-5 pt-4 text-center">
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-5 small text-muted">Prepared By</div>
                                <div class="fw-bold"><?= e($_SESSION['user_name'] ?? 'Yönetici') ?></div>
                            </div>
                            <div class="col-6">
                                <div class="mb-5 small text-muted">Related Account (Signature)</div>
                                <div class="fw-bold">_________________</div>
                            </div>
                        </div>
                    </div>
                    <?php break;
                }
                echo '</div>';
            endforeach; ?>
    </div>
</div>
</div>
<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>