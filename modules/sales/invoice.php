<?php
/**
 * Sales Invoice / Print Page
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$id = (int) get('id');

if ($id <= 0) {
    setFlash('error', __('invalid_sale'));
    redirect(BASE_URL . '/modules/sales/index.php');
}

// Satış
$stmt = $pdo->prepare("
    SELECT s.*, c.first_name, c.last_name, c.phone, c.address, c.unique_id
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE s.id = :id
");
$stmt->execute([':id' => $id]);
$sale = $stmt->fetch();

if (!$sale) {
    die(__('sale_not_found'));
}

$stmtItems = $pdo->prepare("
    SELECT si.*, p.name as product_name, p.dosage_form, p.barcode, p.unit
    FROM sale_items si
    LEFT JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = :id
");
$stmtItems->execute([':id' => $id]);
$items = $stmtItems->fetchAll();

$showFlash = (get('flash') === '1');
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('invoice') ?> #
        <?= $id ?> | BAH <?= __('pharmacy') ?>
    </title>
    <style>
        /* Ekran Stili */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6f9;
            color: #333;
            line-height: 1.5;
            padding: 20px;
        }

        .invoice {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 50px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .inv-brand h2 {
            margin: 0;
            color: #1e293b;
            font-size: 26px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .inv-brand p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .inv-meta {
            text-align: right;
        }

        .inv-num {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
        }

        .inv-info {
            display: flex;
            gap: 40px;
            margin-bottom: 30px;
        }

        .inv-info-box {
            flex: 1;
            background: #f8fafc;
            padding: 15px 20px;
            border-radius: 6px;
        }

        .inv-info-box h4 {
            margin: 0 0 10px;
            font-size: 12px;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .inv-info-box p {
            margin: 0 0 4px;
            font-size: 14px;
            color: #334155;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th,
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
            font-size: 14px;
        }

        th {
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
        }

        td {
            color: #1e293b;
        }

        .right {
            text-align: right;
        }

        .inv-summary {
            width: 350px;
            margin-left: auto;
        }

        .inv-summary table {
            margin-bottom: 0;
        }

        .inv-summary th,
        .inv-summary td {
            padding: 10px 15px;
            border: none;
            border-bottom: 1px solid #f8fafc;
        }

        .total-row {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            border-top: 2px solid #e2e8f0;
        }

        .debt-row {
            color: #ef4444;
            font-weight: 600;
        }

        .inv-footer {
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            margin-top: 40px;
            border-top: 1px solid #f0f0f0;
            padding-top: 20px;
        }

        .thank {
            font-weight: 600;
            color: #64748b;
            display: block;
            margin-bottom: 5px;
        }

        .screen-toolbar {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-print {
            background: #3b82f6;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-print:hover {
            background: #2563eb;
        }

        .btn-back,
        .btn-sales {
            display: inline-block;
            margin-left: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 9px 15px;
            border-radius: 6px;
        }

        .btn-back {
            color: #64748b;
            background: #fff;
            border: 1px solid #cbd5e1;
        }

        .btn-sales {
            color: #fff;
            background: #10b981;
        }

        .flash-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 15px;
            border-radius: 6px;
            max-width: 800px;
            margin: 0 auto 20px auto;
            text-align: center;
            font-size: 15px;
        }

        @media print {
            body {
                background: transparent;
                padding: 0;
                margin: 0;
            }

            .screen-toolbar,
            .flash-success {
                display: none !important;
            }

            .invoice {
                box-shadow: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
                border-radius: 0;
            }

            @page {
                margin: 1cm;
                size: A4 portrait;
            }
        }
    </style>
</head>

<body>
    <?php
    $pageTitle = __('sales_invoice');
    require_once dirname(__DIR__, 2) . '/core/layout_header.php';

    // Şablon ve Veri Hazırlama
    $tpl = getReportTemplate('invoice', $pdo);
    ?>

    <style>
        /* ── Invoice Paper (Screen) ── */
        .invoice-wrapper {
            max-width: 800px;
            margin: 0 auto 40px auto;
        }

        .report-paper {
            background: #ffffff;
            border-radius: 6px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.10);
            padding: 48px 56px;
            min-height: 600px;
            font-size: <?= (int) ($tpl['settings']['font_size'] ?? 14) ?>px;
            line-height: 1.65;
            color: #1e293b;
        }

        /* Logo */
        .report-logo {
            width: 80px;
            height: 80px;
            max-width: 80px;
            max-height: 80px;
            object-fit: contain;
            display: block;
        }

        /* Section spacing */
        .section-box {
            margin-bottom: 28px;
        }

        /* Header divider */
        #section-logo_header {
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px solid #e2e8f0;
        }

        /* Customer / meta band */
        #section-customer_meta {
            background: #f8fafc;
            border-radius: 6px;
            padding: 20px 24px;
            margin-bottom: 28px;
        }

        /* Items table */
        .inv-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .inv-table thead tr {
            background: #1e3a5f;
        }

        .inv-table thead th {
            color: #ffffff !important;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 11px 14px;
            border: none;
        }

        .inv-table tbody tr {
            border-bottom: 1px solid #f0f4f8;
        }

        .inv-table tbody tr:last-child {
            border-bottom: none;
        }

        .inv-table tbody td {
            padding: 11px 14px;
            font-size: 13px;
            color: #334155;
            vertical-align: middle;
        }

        .inv-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        /* Summary totals */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 8px 12px;
            font-size: 13px;
            border: none;
        }

        .summary-table .total-row td {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            border-top: 2px solid #e2e8f0;
            padding-top: 12px;
        }

        /* Print styles */
        @media print {
            header, .sidebar, .topbar,
            .btn-accent, .btn-outline-secondary,
            .nav-label, .sidebar-toggle,
            .d-print-none, .flash-success {
                display: none !important;
            }

            #content {
                margin: 0 !important;
                width: 100% !important;
                background: white !important;
                overflow: visible !important;
            }

            body, html {
                background: white !important;
                overflow: visible !important;
                height: auto !important;
            }

            .page-content { padding: 0 !important; }
            .panel { border: none !important; box-shadow: none !important; margin: 0 !important; }

            @page {
                size: A4 portrait;
                margin: 14mm 16mm;
            }

            .invoice-wrapper { max-width: 100%; margin: 0; }

            .report-paper {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
                min-height: unset;
            }

            .report-logo {
                width: 72px !important;
                height: 72px !important;
                max-width: 72px !important;
                max-height: 72px !important;
            }

            .section-box { margin-bottom: 22px !important; }
            #section-logo_header { margin-bottom: 22px; padding-bottom: 16px; }
            #section-customer_meta { padding: 14px 16px; }

            .inv-table thead tr { background: #1e3a5f !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .inv-table thead th { color: #ffffff !important; }
            .inv-table tbody tr:nth-child(even) { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            .summary-table td { font-size: 12px; }
            .summary-table .total-row td { font-size: 14px; }
        }
    </style>

    <div class="invoice-wrapper">

            <?php if ($showFlash): ?>
                <div class="alert alert-success d-print-none mb-4 shadow-sm border-0 d-flex align-items-center">
                    <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                    <div><strong><?= __('sale_completed_title') ?></strong> <?= __('invoice_generated_help') ?></div>
                </div>
            <?php endif; ?>

            <!-- Kontroller -->
            <div class="d-flex align-items-center justify-content-between mb-4 d-print-none">
                <h4 style="margin:0;"><i class="bi bi-file-earmark-text me-2"></i><?= __('sales_invoice') ?></h4>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/modules/settings/reports.php?type=invoice"
                        class="btn btn-outline-info btn-sm">
                        <i class="bi bi-pencil-square me-1"></i><?= __('edit_design') ?></a>
                    <button onclick="window.print()" class="btn-accent btn-sm">
                        <i class="bi bi-printer me-1"></i><?= __('print') ?> / PDF</button>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm"><?= __('back') ?></a>
                </div>
            </div>

            <div class="panel report-paper shadow-sm invoice-card">
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
                                    <h2 class="fw-bold m-0"><?= mb_strtoupper(__('sales_invoice'), 'UTF-8') ?></h2>
                                    <div class="fw-bold"><?= __('invoice_no') ?>: #<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></div>
                                    <div class="text-muted small"><?= date('d.m.Y H:i', strtotime($sale['created_at'])) ?></div>
                                </div>
                            </div>
                            <?php break;

                        case 'customer_meta': ?>
                            <div class="row">
                                <div class="col-6">
                                    <h6 class="fw-bold opacity-75 text-uppercase mb-2" style="font-size:11px;">
                                        <?= mb_strtoupper(__('customer_info'), 'UTF-8') ?>
                                    </h6>
                                    <?php if ($sale['first_name']): ?>
                                        <div class="fs-5 fw-bold"><?= e($sale['first_name'] . ' ' . $sale['last_name']) ?></div>
                                        <div class="text-muted"><?= e($sale['phone'] ?: '—') ?></div>
                                        <div class="text-muted small"><?= e($sale['address'] ?: '—') ?></div>
                                    <?php else: ?>
                                        <div class="fs-5 fw-bold"><?= mb_strtoupper(__('cash_customer'), 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-6 text-end">
                                    <h6 class="fw-bold opacity-75 text-uppercase mb-2" style="font-size:11px;">
                                        <?= mb_strtoupper(__('sales_history'), 'UTF-8') ?>
                                    </h6>
                                    <div><?= __('date') ?>: <strong><?= date('d.m.Y', strtotime($sale['created_at'])) ?></strong>
                                    </div>
                                    <div><?= __('payment_method') ?>: <strong><?= __('cash') ?></strong></div>
                                    <?php if ($sale['due_date'] && $sale['remaining_amount'] > 0): ?>
                                        <div class="mt-1" style="color:var(--danger);"><?= __('due_date') ?>:
                                            <strong><?= date('d.m.Y', strtotime($sale['due_date'])) ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($sale['note']): ?>
                                        <div class="mt-2 small text-muted"><em><?= __('note') ?>: <?= e($sale['note']) ?></em></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php break;

                        case 'main_table': ?>
                            <div class="mt-1">
                                <table class="table table-bordered align-middle inv-table">
                                    <thead>
                                        <tr>
                                            <th><?= __('product_service') ?></th>
                                            <th class="text-center"><?= __('barcode') ?></th>
                                            <th class="text-end"><?= __('price') ?></th>
                                            <th class="text-center"><?= __('quantity') ?></th>
                                            <th class="text-end"><?= __('total') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($items)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted small italic">
                                                    <?= __('no_data') ?>
                                                </td>
                                            </tr>
                                        <?php else:
                                            foreach ($items as $i): ?>
                                                <tr>
                                                    <td><strong><?= e($i['product_name'] ?? 'Unknown Product (Deleted)') ?></strong><br><small><?= e($i['dosage_form'] ?? '') ?></small>
                                                    </td>
                                                    <td class="text-center small"><?= e($i['barcode'] ?: '—') ?></td>
                                                    <td class="text-end"><?= formatMoney((float) $i['unit_price']) ?></td>
                                                    <td class="text-center"><?= (int) $i['quantity'] ?>
                                                        <?= e($i['unit'] ?? 'Piece') ?>
                                                    </td>
                                                    <td class="text-end fw-bold"><?= formatMoney((float) $i['total_price']) ?></td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php break;

                        case 'summary_bar': ?>
                            <div class="d-flex justify-content-end">
                                <div style="width: 300px;">
                                    <table class="table table-sm summary-table">
                                        <tr>
                                            <td class="text-muted"><?= __('subtotal') ?></td>
                                            <td class="text-end fw-bold"><?= formatMoney((float) $sale['total_amount']) ?></td>
                                        </tr>
                                        <?php if ($sale['discount_value'] > 0): ?>
                                            <tr>
                                                <td class="text-muted"><?= __('discount') ?></td>
                                                <td class="text-end text-danger">
                                                    -<?= formatMoney($sale['total_amount'] - $sale['final_amount']) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr class="total-row">
                                            <td><?= mb_strtoupper(__('total'), 'UTF-8') ?></td>
                                            <td class="text-end"><?= formatMoney((float) $sale['final_amount']) ?></td>
                                        </tr>
                                        <?php if ($sale['paid_amount'] > 0): ?>
                                            <tr>
                                                <td class="text-success small fw-bold">
                                                    <?= mb_strtoupper(__('paid_amount'), 'UTF-8') ?>
                                                </td>
                                                <td class="text-end text-success fw-bold">
                                                    <?= formatMoney((float) $sale['paid_amount']) ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if ($sale['remaining_amount'] > 0): ?>
                                            <tr>
                                                <td class="text-danger small fw-bold">
                                                    <?= mb_strtoupper(__('remaining_debt'), 'UTF-8') ?>
                                                </td>
                                                <td class="text-end text-danger fw-bold">
                                                    <?= formatMoney((float) $sale['remaining_amount']) ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                            <?php break;

                        case 'notes_footer': ?>
                            <div class="mt-5 pt-4 border-top text-center text-muted" style="font-size:0.85em;">
                                <p class="fw-bold mb-1"><?= __('healthy_days') ?></p>
                                <p><?= __('non_financial_doc') ?></p>
                                <div class="mt-3">
                                    <div class="d-inline-block border-top pt-2" style="width:150px;"><?= __('delivered_by') ?></div>
                                    <div class="d-inline-block" style="width:50px;"></div>
                                    <div class="d-inline-block border-top pt-2" style="width:150px;"><?= __('delivered_to') ?></div>
                                </div>
                            </div>
                            <?php break;
                    }
                    echo '</div>';
                endforeach; ?>
            </div>
    </div>
    <?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>