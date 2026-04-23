<?php
/**
 * Customer Detail
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$id = (int) get('id');

if ($id <= 0) {
    setFlash('error', __('customer_not_found'));
    redirect(BASE_URL . '/modules/customers/index.php');
}

// Müşteri yükle
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
$stmt->execute([':id' => $id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', __('customer_not_found'));
    redirect(BASE_URL . '/modules/customers/index.php');
}

// Aksiyonlar (Ödeme Alma veya Manuel Borçlandırma)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die(__('error'));
    }

    $action = post('action');
    $amount = (float) post('amount');
    $note = post('note');

    if ($action === 'payment') {
        $method = post('method', 'cash');
        $saleId = (int) post('sale_id');

        if ($amount <= 0) {
            setFlash('error', __('price_required'));
        } else {
            $pdo->beginTransaction();
            try {
                // 1. Ödeme kaydı
                if ($saleId > 0) {
                    $stmtP = $pdo->prepare("INSERT INTO payments (customer_id, sale_id, amount, method, note) VALUES (:cid, :sid, :amt, :m, :n)");
                    $stmtP->execute([':cid' => $id, ':sid' => $saleId, ':amt' => $amount, ':m' => $method, ':n' => $note]);
                } else {
                    $stmtP = $pdo->prepare("INSERT INTO payments (customer_id, amount, method, note) VALUES (:cid, :amt, :m, :n)");
                    $stmtP->execute([':cid' => $id, ':amt' => $amount, ':m' => $method, ':n' => $note]);
                }
                $receiptId = $pdo->lastInsertId();

                // 2. Müşteri bakiyesi güncelle (Tahsilat borcu azaltır)
                $pdo->prepare("UPDATE customers SET total_debt = total_debt - :amt WHERE id = :cid")
                    ->execute([':amt' => $amount, ':cid' => $id]);

                // 3. Fatura ödemesi ise fatura kalanını güncelle
                if ($saleId > 0) {
                    $pdo->prepare("UPDATE sales SET paid_amount = paid_amount + :amt WHERE id = :sid")
                        ->execute([':amt' => $amount, ':sid' => $saleId]);
                }

                $pdo->commit();

                $_SESSION['last_payment_receipt_id'] = $receiptId;
                setFlash('success', __('success'));
                redirect(BASE_URL . '/modules/customers/detail.php?id=' . $id);
            } catch (Exception $e) {
                $pdo->rollBack();
                setFlash('error', 'Error occurred during registration: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'add_debt') {
        // MAUEL BORÇ DEKONTU (Ürün satışı yok, sadece bakiye artırımı)
        if ($amount <= 0) {
            setFlash('error', __('price_required'));
        } else {
            $pdo->beginTransaction();
            try {
                // sales tablosunda sahte bir satış gibi gösterelim ya da doğrudan borç ekleyelim
                // Sistemin yürümesi için "satış" olarak ama itemsız bir kayıt eklemek en sağlıklısı
                $stmt = $pdo->prepare("
                    INSERT INTO sales (customer_id, total_amount, discount_type, discount_value, final_amount, paid_amount, remaining_amount, note) 
                    VALUES (:cid, :amt1, 'none', 0, :amt2, 0, :amt3, :note)
                ");
                $stmt->execute([':cid' => $id, ':amt1' => $amount, ':amt2' => $amount, ':amt3' => $amount, ':note' => '(Borç Dekontu) ' . $note]);
                $saleId = $pdo->lastInsertId();

                // Log kaydı
                logAction('Debt Note', "Manual debt of {$amount} added for Customer #{$id}: {$note}");

                // Müşteri kartına borç olarak yansıt
                $pdo->prepare("UPDATE customers SET total_debt = total_debt + :amt WHERE id = :cid")
                    ->execute([':amt' => $amount, ':cid' => $id]);

                $pdo->commit();
                setFlash('success', __('success'));
                redirect(BASE_URL . '/modules/customers/detail.php?id=' . $id);
            } catch (Exception $e) {
                $pdo->rollBack();
                setFlash('error', 'Error occurred during registration: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete_sale') {
        $saleId = (int) post('sale_id');
        $pdo->beginTransaction();
        try {
            $stmtS = $pdo->prepare("SELECT * FROM sales WHERE id = :sid AND customer_id = :cid");
            $stmtS->execute([':sid' => $saleId, ':cid' => $id]);
            $sale = $stmtS->fetch();

            if ($sale) {
                // 1. Borcu geri düş
                $debtToReduce = $sale['final_amount'] - $sale['paid_amount'];
                $pdo->prepare("UPDATE customers SET total_debt = total_debt - :amt WHERE id = :cid")
                    ->execute([':amt' => $debtToReduce, ':cid' => $id]);

                // 2. Stokları geri al
                $items = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = :sid");
                $items->execute([':sid' => $saleId]);
                while ($item = $items->fetch()) {
                    $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :pid")
                        ->execute([':qty' => $item['quantity'], ':pid' => $item['product_id']]);
                }

                $pdo->prepare("DELETE FROM sales WHERE id = :sid")->execute([':sid' => $saleId]);
                logAction('Sale Deleted', "Customer #{$id} Sale #{$saleId} deleted.");
                $pdo->commit();
                setFlash('success', __('success'));
            }
            redirect(BASE_URL . '/modules/customers/detail.php?id=' . $id);
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', 'Deletion error: ' . $e->getMessage());
        }
    } elseif ($action === 'delete_payment') {
        $pid_to_del = (int) post('payment_id');
        $pdo->beginTransaction();
        try {
            $stmtP = $pdo->prepare("SELECT * FROM payments WHERE id = :pid AND customer_id = :cid");
            $stmtP->execute([':pid' => $pid_to_del, ':cid' => $id]);
            $payment = $stmtP->fetch();

            if ($payment) {
                $pdo->prepare("UPDATE customers SET total_debt = total_debt + :amt WHERE id = :cid")
                    ->execute([':amt' => $payment['amount'], ':cid' => $id]);

                if ($payment['sale_id']) {
                    $pdo->prepare("UPDATE sales SET paid_amount = paid_amount - :amt, remaining_amount = remaining_amount + :amt WHERE id = :sid")
                        ->execute([':amt' => $payment['amount'], ':sid' => $payment['sale_id']]);
                }

                $pdo->prepare("DELETE FROM payments WHERE id = :pid")->execute([':pid' => $pid_to_del]);
                logAction('Payment Deleted', "Customer #{$id} Payment #{$pid_to_del} deleted.");
                $pdo->commit();
                setFlash('success', __('success'));
            }
            redirect(BASE_URL . '/modules/customers/detail.php?id=' . $id);
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', 'Deletion error: ' . $e->getMessage());
        }
    } elseif ($action === 'recalc_debt') {
        $stmt = $pdo->prepare("SELECT SUM(final_amount - paid_amount) FROM sales WHERE customer_id = :cid");
        $stmt->execute([':cid' => $id]);
        $newDebt = (float) $stmt->fetchColumn();

        $pdo->prepare("UPDATE customers SET total_debt = :d WHERE id = :cid")
            ->execute([':d' => $newDebt, ':cid' => $id]);

        setFlash('success', 'Customer balance repaired by scanning all records.');
        redirect(BASE_URL . '/modules/customers/detail.php?id=' . $id);
    }
}

// İstatistik & Listeler...
$sales = $pdo->prepare("
    SELECT s.*, 
           (SELECT SUM(quantity) FROM sale_items WHERE sale_id = s.id) as item_count 
    FROM sales s
    WHERE s.customer_id = :cid 
    ORDER BY s.created_at DESC
");
$sales->execute([':cid' => $id]);
$sales = $sales->fetchAll();
$payments = $pdo->query("SELECT * FROM payments WHERE customer_id = $id AND amount > 0 ORDER BY created_at DESC")->fetchAll();

$pendingSales = array_filter($sales, function ($s) {
    return $s['remaining_amount'] > 0;
});
$totalSpent = array_sum(array_column($sales, 'final_amount'));

$methodLabels = [
    'cash' => [__('cash'), 'badge-stock-ok'],
    'card' => [__('card'), 'badge-stock-low'],
    'transfer' => [__('transfer'), 'badge-stock-low'],
    'other' => [__('other'), 'badge-stock-out']
];

$pageTitle = e($customer['first_name'] . ' ' . $customer['last_name']);
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<!-- Son Ödeme Makbuzu Uyarı -->
<?php if (isset($_SESSION['last_payment_receipt_id'])):
    $receiptId = $_SESSION['last_payment_receipt_id'];
    unset($_SESSION['last_payment_receipt_id']);
    ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-check-circle me-2"></i><?= __('success') ?>. Would you like to print the receipt?
        </div>
        <a href="receipt.php?id=<?= $receiptId ?>" target="_blank" class="btn btn-sm btn-success text-nowrap"><i
                class="bi bi-printer me-1"></i><?= __('print') ?></a>
    </div>
<?php endif; ?>
<div class="panel mb-4">
    <div class="panel-body">
        <div class="d-flex align-items-center gap-4 flex-wrap">

            <!-- Avatar -->
            <div style="width:70px;height:70px;border-radius:50%;background:var(--accent-soft);
                        display:flex;align-items:center;justify-content:center;
                        font-size:30px;border:2px solid var(--accent);flex-shrink:0;">
                <?= mb_strtoupper(mb_substr($customer['first_name'], 0, 1)) ?>
            </div>

            <!-- Bilgiler -->
            <div style="flex:1;">
                <h4 style="margin:0;font-size:22px;">
                    <?= e($customer['first_name'] . ' ' . $customer['last_name']) ?>
                </h4>
                <div style="color:var(--text-muted);font-size:13px;margin-top:4px;">
                    <?php if ($customer['phone']): ?>
                        <i class="bi bi-telephone me-1"></i>
                        <?= e($customer['phone']) ?>
                    <?php endif; ?>
                    <?php if ($customer['address']): ?>
                        <span class="ms-3"><i class="bi bi-geo-alt me-1"></i>
                            <?= e($customer['address']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">
                    <i class="bi bi-fingerprint me-1"></i>
                    <?= e($customer['unique_id']) ?> &mdash; <?= __('due_days') ?>:
                    <?= (int) $customer['payment_due_days'] ?> <?= __('days') ?> &mdash; <?= __('customer_since') ?>
                    <?= date('d.m.Y', strtotime($customer['created_at'])) ?>.
                </div>
            </div>

            <!-- Özet Sayılar -->
            <div class="d-flex gap-3 flex-wrap">
                <div class="text-center p-3"
                    style="background:rgba(14,165,233,0.08);border-radius:10px;min-width:100px;">
                    <div style="font-size:22px;font-weight:700;">
                        <?= count($sales) ?>
                    </div>
                    <div style="font-size:11px;color:var(--text-muted);">
                        <?= __('sale_count') ?>
                    </div>
                </div>
                <div class="text-center p-3"
                    style="background:rgba(34,197,94,0.08);border-radius:10px;min-width:100px;">
                    <div style="font-size:18px;font-weight:700;color:var(--success);">
                        <?= formatMoney($totalSpent) ?>
                    </div>
                    <div style="font-size:11px;color:var(--text-muted);">
                        <?= __('total_spent') ?>
                    </div>
                </div>
                <div class="text-center p-3"
                    style="background:rgba(<?= $customer['total_debt'] > 0 ? '239,68,68' : '34,197,94' ?>,0.08);border-radius:10px;min-width:140px;">
                    <div
                        style="font-size:20px;font-weight:700;color:<?= $customer['total_debt'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                        <?php if ($customer['total_debt'] > 0.001): ?>
                            <span class="d-inline-flex align-items-center justify-content-center me-2"
                                style="width:24px;height:24px;background:rgba(239,68,68,0.15);border:1px solid var(--danger);border-radius:6px;vertical-align:middle;">
                                <i class="bi bi-dash"></i>
                            </span>
                            <?= formatMoney((float) $customer['total_debt']) ?>
                        <?php elseif ($customer['total_debt'] < -0.001): ?>
                            <span class="d-inline-flex align-items-center justify-content-center me-2"
                                style="width:24px;height:24px;background:rgba(34,197,94,0.15);border:1px solid var(--success);border-radius:6px;vertical-align:middle;">
                                <i class="bi bi-plus"></i>
                            </span>
                            <?= formatMoney(abs((float) $customer['total_debt'])) ?>
                        <?php else: ?>
                            <?= formatMoney(0) ?>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:11px;color:var(--text-muted);">
                        <?= $customer['total_debt'] > 0 ? __('remaining_debt') : __('creditor_advance') ?>
                    </div>
                </div>
            </div>

            <!-- Butonlar -->
            <div class="d-flex flex-column gap-2" style="min-width:140px;">
                <div class="d-flex gap-1">
                    <a href="ledger.php?id=<?= $id ?>" class="btn btn-outline-info btn-sm flex-grow-1"><i
                            class="bi bi-file-earmark-spreadsheet me-1"></i>
                        <?= __('statement') ?>
                    </a>
                    <form method="POST" action="detail.php?id=<?= $id ?>" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="recalc_debt">
                        <button type="submit" class="btn btn-outline-warning btn-sm"
                            title="Recalculate Debt (Synchronize)">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </form>
                </div>
                <a href="form.php?id=<?= $id ?>" class="btn-accent btn-sm text-center"><i class="bi bi-pencil me-1"></i>
                    <?= __('edit') ?>
                </a>
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#debtModal">
                    <i class="bi bi-journal-plus me-1"></i><?= __('manual_debt') ?></button>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    <i class="bi bi-cash-coin me-1"></i><?= __('receive_payment') ?></button>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Satışlar Tablosu -->
    <div class="col-md-7">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-cart3 me-2"></i><?= __('sales_history') ?><span class="badge bg-secondary ms-2">
                        <?= count($sales) ?>
                    </span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table-dark-custom">
                    <thead>
                        <tr>
                            <th>
                                <?= __('date') ?>
                            </th>
                            <th><?= __('product_count') ?></th>
                            <th><?= __('total') ?></th>
                            <th><?= __('paid') ?></th>
                            <th><?= __('remaining') ?></th>
                            <th><?= __('due_date') ?></th>
                            <th><?= __('invoice') ?></th>
                            <th><?= __('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted"><?= __('no_data') ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sales as $s): ?>
                                <tr class="<?= $s['remaining_amount'] > 0 ? 'row-low' : '' ?>">
                                    <td style="font-size:12px;">
                                        <?= date('d.m.Y H:i', strtotime($s['created_at'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($s['item_count'] > 0): ?>
                                            <?= (int) $s['item_count'] ?>             <?= __('items') ?>
                                        <?php else: ?>
                                            <span style="color:var(--danger);font-size:12px;"><i
                                                    class="bi bi-journal-text me-1"></i><?= __('debt_note') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= formatMoney((float) $s['final_amount']) ?>
                                    </td>
                                    <td style="color:var(--success);">
                                        <?= formatMoney((float) $s['paid_amount']) ?>
                                    </td>
                                    <td>
                                        <?php if ($s['remaining_amount'] > 0): ?>
                                            <strong style="color:var(--danger);">
                                                <?= formatMoney((float) $s['remaining_amount']) ?>
                                            </strong>
                                        <?php else: ?>
                                            <span style="color:var(--success);font-size:13px;"><?= __('paid') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($s['due_date']):
                                            $days = (int) ceil((strtotime($s['due_date']) - time()) / 86400);
                                            $color = $days < 0 ? 'var(--danger)' : ($days <= 7 ? 'var(--warning)' : 'var(--text-muted)');
                                            ?>
                                            <div style="font-size:12px; color:<?= $color ?>;">
                                                <?= date('d.m.Y', strtotime($s['due_date'])) ?>
                                                <?php if ($s['remaining_amount'] > 0): ?>
                                                    <div style="font-size:10px;">
                                                        <?= $days < 0 ? abs($days) . ' ' . __('late') : $days . ' ' . __('left') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($s['invoice_path']): ?>
                                            <a href="<?= BASE_URL ?>/storage/invoices/<?= e($s['invoice_path']) ?>" target="_blank"
                                                class="btn-sm-icon"><i class="bi bi-file-earmark-pdf"></i></a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>/modules/sales/invoice.php?id=<?= $s['id'] ?>" target="_blank"
                                                class="btn-sm-icon"><i class="bi bi-printer"></i></a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="detail.php?id=<?= $id ?>"
                                            onsubmit="return confirm('Are you sure you want to delete this operation? The debt balance will be recalculated.')"
                                            style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="delete_sale">
                                            <input type="hidden" name="sale_id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="btn-sm-icon btn-delete" title="Delete"><i
                                                    class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ödeme Geçmişi -->
    <div class="col-md-5">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-cash-stack me-2"></i><?= __('payment_history') ?><span
                        class="badge bg-secondary ms-2">
                        <?= count($payments) ?>
                    </span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table-dark-custom">
                    <thead>
                        <tr>
                            <th>
                                <?= __('date') ?>
                            </th>
                            <th><?= __('amount') ?></th>
                            <th><?= __('method') ?></th>
                            <th><?= __('receipt') ?></th>
                            <th><?= __('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted"><?= __('no_data') ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $p):
                                [$mlabel, $mcls] = $methodLabels[$p['method']] ?? [$p['method'], 'badge-stock-low'];
                                ?>
                                <tr>
                                    <td style="font-size:12px;">
                                        <?= date('d.m.Y H:i', strtotime($p['created_at'])) ?>
                                    </td>
                                    <td><strong style="color:var(--success);">
                                            <?= formatMoney((float) $p['amount']) ?>
                                        </strong></td>
                                    <td><span class="<?= $mcls ?>">
                                            <?= $mlabel ?>
                                        </span></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/modules/customers/receipt.php?id=<?= $p['id'] ?>"
                                            target="_blank" class="btn-sm-icon me-1"><i class="bi bi-printer"></i></a>
                                        <form method="POST" action="detail.php?id=<?= $id ?>"
                                            onsubmit="return confirm('Are you sure you want to delete this payment? The debt balance will be recalculated.')"
                                            style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="delete_payment">
                                            <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn-sm-icon btn-delete" title="Delete"><i
                                                    class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ── Ödeme Modal ─────────────────────────────────────── -->
<div class="modal fade modal-dark" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">
                    <i class="bi bi-cash-coin me-2"></i> <?= __('receive_payment') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="detail.php?id=<?= $id ?>" data-once>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="payment">
                <div class="modal-body">

                    <div class="mb-3 p-3" style="background:rgba(239,68,68,0.08);border-radius:10px;text-align:center;">
                        <div style="font-size:12px;color:var(--text-muted);"><?= __('total_debt') ?></div>
                        <div style="font-size:24px;font-weight:700;color:var(--danger);">
                            <?= formatMoney((float) $customer['total_debt']) ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('payment_amount') ?><span
                                style="color:#ef9a9a">*</span></label>
                        <input type="number" name="amount" class="form-control-dark" step="0.01" min="0.01"
                            data-positive required placeholder="0.00">
                    </div>

                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('payment_method') ?></label>
                        <select name="method" class="form-select-dark">
                            <option value="cash"><?= __('cash') ?></option>
                            <option value="card"><?= __('card') ?></option>
                            <option value="transfer"><?= __('transfer') ?></option>
                            <option value="other"><?= __('other') ?></option>
                        </select>
                    </div>

                    <?php if (!empty($pendingSales)): ?>
                        <div class="mb-3">
                            <label class="form-label-dark"><?= __('related_sale') ?> (<?= __('optional') ?>)</label>
                            <select name="sale_id" class="form-select-dark">
                                <option value="">--- <?= __('general_payment_advance') ?> ---</option>
                                <?php foreach ($pendingSales as $ps): ?>
                                    <option value="<?= $ps['id'] ?>"><?= __('sale') ?> #
                                        <?= $ps['id'] ?> &mdash;
                                        <?= date('d.m.Y', strtotime($ps['created_at'])) ?> (<?= __('remaining') ?>:
                                        <?= formatMoney((float) $ps['remaining_amount']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('note') ?></label>
                        <input type="text" name="note" class="form-control-dark" placeholder="<?= __('optional') ?>">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <?= __('cancel') ?>
                    </button>
                    <button type="submit" class="btn-accent">
                        <i class="bi bi-check-lg me-1"></i> <?= __('save') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Borçlandır Modal ─────────────────────────────────────── -->
<div class="modal fade modal-dark" id="debtModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom:1px solid rgba(239,68,68,0.2);">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-journal-plus me-2"></i> <?= __('manual_debt_receipt') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="detail.php?id=<?= $id ?>" data-once>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="add_debt">
                <div class="modal-body">
                    <div class="alert alert-danger" style="font-size:13px;">
                        <i class="bi bi-info-circle me-1"></i> <?= __('manual_debt_help') ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('payment_amount') ?><span
                                style="color:#ef9a9a">*</span></label>
                        <input type="number" name="amount" class="form-control-dark" step="0.01" min="0.01"
                            data-positive required placeholder="0.00">
                    </div>

                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('note') ?></label>
                        <input type="text" name="note" class="form-control-dark" placeholder="<?= __('optional') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <?= __('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-plus-circle me-1"></i><?= __('add_debt') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>