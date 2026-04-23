<?php
/**
 * Müşteri Cari Hesap Ekstresi (Ledger)
 *
 * Müşterinin tüm satış ve ödeme işlemlerini tarih sırası ile birleştirir,
 * Satır satır yürüyen bakiye hesaplar.
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$id = (int) get('id');

if ($id <= 0) {
    setFlash('error', 'Geçersiz müşteri.');
    redirect(BASE_URL . '/modules/customers/index.php');
}

// Müşteriyi getir
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
$stmt->execute([':id' => $id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Müşteri bulunamadı.');
    redirect(BASE_URL . '/modules/customers/index.php');
}

// Tüm işlemleri tek sorguda çekip tarih sırasına dizme
$sql = "
    SELECT id as ref_id, 'sale' as type, final_amount as amount, created_at, note 
    FROM sales 
    WHERE customer_id = :cid1
    
    UNION ALL
    
    SELECT id as ref_id, 'payment' as type, amount, created_at, note 
    FROM payments 
    WHERE customer_id = :cid2
    
    ORDER BY created_at ASC
";
$stmtL = $pdo->prepare($sql);
$stmtL->execute([':cid1' => $id, ':cid2' => $id]);
$ledger = $stmtL->fetchAll();

$pageTitle = __('txt_cari_hesap_ekstresi');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';

// Şablon ve Veri Hazırlama
$tpl = getReportTemplate('ledger', $pdo);

// Özet verileri hazırla
$totalDebt = $pdo->prepare("SELECT SUM(final_amount) FROM sales WHERE customer_id = ?");
$totalDebt->execute([$id]);
$totalPaid = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE customer_id = ?");
$totalPaid->execute([$id]);

$summary = [
    'total_debt' => (float) ($totalDebt->fetchColumn() ?: 0),
    'total_paid' => (float) ($totalPaid->fetchColumn() ?: 0)
];

// İşlem loglarını (ledger) formatla
$logs = [];
foreach ($ledger as $item) {
    if ($item['type'] === 'sale') {
        $logs[] = ['date' => $item['created_at'], 'type' => 'Satış', 'note' => $item['note'], 'debt' => (float) $item['amount'], 'credit' => 0];
    } else {
        $logs[] = ['date' => $item['created_at'], 'type' => 'Tahsilat', 'note' => $item['note'], 'debt' => 0, 'credit' => (float) $item['amount']];
    }
}
?>

<style>
    /* Ekran Düzenlemeleri */
    @media screen {

        <?php if ($tpl['settings']['hide_header_nav'] ?? true): ?>
            .topbar-right,
            .topbar-title {
                display: none !important;
            }

            .topbar {
                background: transparent !important;
                border: none !important;
            }

        <?php endif; ?>
    }

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
        padding: 40px;
        color: #333;
        font-size:
            <?= (int) ($tpl['settings']['font_size'] ?? 14) ?>
            px;
        min-height: 800px;
        line-height: 1.6;
    }

    .report-logo {
        width:
            <?= (int) ($tpl['settings']['logo_size'] ?? 60) ?>
            px;
        height: auto;
    }

    .section-box {
        margin-bottom: 25px;
    }

    .table th {
        background: #f1f5f9 !important;
        color: #334155 !important;
        font-weight: 700;
        text-transform: uppercase;
    }
</style>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex align-items-center justify-content-between mb-4 d-print-none">
            <h4 style="margin:0;"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Customer Ledger</h4>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/modules/settings/reports.php?type=ledger" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-pencil-square me-1"></i>Edit Design</a>
                <button onclick="window.print()" class="btn-accent btn-sm">
                    <i class="bi bi-printer me-1"></i>Print / PDF</button>
                <a href="detail.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Back to Customer</a>
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
                            <div>
                                <?php if (file_exists(dirname(__DIR__, 2) . '/storage/images/logo.png')): ?>
                                    <img src="<?= BASE_URL ?>/storage/images/logo.png" class="report-logo">
                                <?php else: ?>
                                    <h4 class="fw-bold m-0">BAH PHARMACY</h4>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <h3 class="fw-bold m-0">CUSTOMER LEDGER</h3>
                                <div class="text-muted small">Prepared on: <?= date('d.m.Y H:i') ?></div>
                            </div>
                        </div>
                        <?php break;

                    case 'customer_meta': ?>
                        <div class="row mt-4">
                            <div class="col-6">
                                <h6 class="fw-bold opacity-75 text-uppercase mb-2" style="font-size:11px;">CUSTOMER INFORMATION</h6>
                                <div class="fs-5 fw-bold"><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></div>
                                <div class="text-muted small">ID: <?= e($customer['unique_id']) ?></div>
                                <div class="text-muted small"><?= e($customer['address']) ?></div>
                            </div>
                            <div class="col-6 text-end">
                                <h6 class="fw-bold opacity-75 text-uppercase mb-2" style="font-size:11px;">ACCOUNT SUMMARY</h6>
                                <div>Total Debt: <strong><?= formatMoney($summary['total_debt']) ?></strong></div>
                                <div>Total Paid: <strong><?= formatMoney($summary['total_paid']) ?></strong></div>
                                <div class="mt-1 fs-5">Remaining Balance: <strong
                                        class="text-accent"><?= formatMoney($customer['total_debt']) ?></strong></div>
                            </div>
                        </div>
                        <?php break;

                    case 'main_table': ?>
                        <div class="mt-4">
                            <table class="table table-bordered table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:15%">Date</th>
                                        <th style="width:10%">Type</th>
                                        <th>Description</th>
                                        <th class="text-end" style="width:15%">Debt</th>
                                        <th class="text-end" style="width:15%">Credit</th>
                                        <th class="text-end" style="width:15%">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $runningBalance = 0;
                                    foreach ($logs as $log):
                                        $runningBalance += ($log['debt'] - $log['credit']);
                                        ?>
                                        <tr>
                                            <td class="small"><?= date('d.m.Y H:i', strtotime($log['date'])) ?></td>
                                            <td><span
                                                    class="badge bg-<?= $log['debt'] > 0 ? 'danger' : 'success' ?>-soft text-<?= $log['debt'] > 0 ? 'danger' : 'success' ?>"><?= $log['type'] ?></span>
                                            </td>
                                            <td class="small"><?= e($log['note']) ?></td>
                                            <td class="text-end"><?= $log['debt'] > 0 ? formatMoney($log['debt']) : '-' ?></td>
                                            <td class="text-end"><?= $log['credit'] > 0 ? formatMoney($log['credit']) : '-' ?></td>
                                            <td class="text-end fw-bold"><?= formatMoney($runningBalance) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php break;

                    case 'notes_footer': ?>
                        <div class="mt-5 pt-3 text-center text-muted small">
                            <p>This statement is for informational purposes only. Please contact us for overdue payments.</p>
                            <div class="mt-4 d-flex justify-content-around">
                                <div class="border-top pt-2" style="width:150px;">Stamp / Signature</div>
                                <div class="border-top pt-2" style="width:150px;">Customer Approval</div>
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