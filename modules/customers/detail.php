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
                // 1. Ödeme kaydı ve Fatura kalanını güncelle
                if ($saleId > 0) {
                    $stmtP = $pdo->prepare("INSERT INTO payments (customer_id, sale_id, amount, method, note) VALUES (:cid, :sid, :amt, :m, :n)");
                    $stmtP->execute([':cid' => $id, ':sid' => $saleId, ':amt' => $amount, ':m' => $method, ':n' => $note]);
                    $receiptId = $pdo->lastInsertId();

                    $pdo->prepare("UPDATE sales SET paid_amount = paid_amount + :amt1, remaining_amount = remaining_amount - :amt2 WHERE id = :sid")
                        ->execute([':amt1' => $amount, ':amt2' => $amount, ':sid' => $saleId]);
                } else {
                    // General payment: Auto-allocate to pending sales
                    $pendingSales = $pdo->prepare("SELECT id, remaining_amount FROM sales WHERE customer_id = :cid AND remaining_amount > 0 ORDER BY due_date ASC, created_at ASC");
                    $pendingSales->execute([':cid' => $id]);
                    $salesToPay = $pendingSales->fetchAll();

                    $remainingToAllocate = $amount;
                    $receiptId = null;

                    foreach ($salesToPay as $ps) {
                        if ($remainingToAllocate <= 0) break;

                        $allocateAmt = min($remainingToAllocate, (float)$ps['remaining_amount']);
                        
                        $stmtP = $pdo->prepare("INSERT INTO payments (customer_id, sale_id, amount, method, note) VALUES (:cid, :sid, :amt, :m, :n)");
                        $stmtP->execute([':cid' => $id, ':sid' => $ps['id'], ':amt' => $allocateAmt, ':m' => $method, ':n' => $note]);
                        if (!$receiptId) $receiptId = $pdo->lastInsertId();

                        $pdo->prepare("UPDATE sales SET paid_amount = paid_amount + :amt1, remaining_amount = remaining_amount - :amt2 WHERE id = :sid")
                            ->execute([':amt1' => $allocateAmt, ':amt2' => $allocateAmt, ':sid' => $ps['id']]);

                        $remainingToAllocate -= $allocateAmt;
                    }

                    // If still money left (Advance)
                    if ($remainingToAllocate > 0) {
                        $stmtP = $pdo->prepare("INSERT INTO payments (customer_id, amount, method, note) VALUES (:cid, :amt, :m, :n)");
                        $stmtP->execute([':cid' => $id, ':amt' => $remainingToAllocate, ':m' => $method, ':n' => $note]);
                        if (!$receiptId) $receiptId = $pdo->lastInsertId();
                    }
                }

                // 2. Müşteri bakiyesi güncelle (Tahsilat borcu azaltır)
                $pdo->prepare("UPDATE customers SET total_debt = total_debt - :amt WHERE id = :cid")
                    ->execute([':amt' => $amount, ':cid' => $id]);

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
                    $pdo->prepare("UPDATE sales SET paid_amount = paid_amount - :amt1, remaining_amount = remaining_amount + :amt2 WHERE id = :sid")
                        ->execute([':amt1' => $payment['amount'], ':amt2' => $payment['amount'], ':sid' => $payment['sale_id']]);
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
        $pdo->beginTransaction();
        try {
            // Correct calculation: Sum of all sales minus Sum of all payments
            $stmtS = $pdo->prepare("SELECT SUM(final_amount) FROM sales WHERE customer_id = :cid");
            $stmtS->execute([':cid' => $id]);
            $totalSales = (float) $stmtS->fetchColumn();

            $stmtP = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE customer_id = :cid");
            $stmtP->execute([':cid' => $id]);
            $totalPayments = (float) $stmtP->fetchColumn();

            $newDebt = $totalSales - $totalPayments;

            $pdo->prepare("UPDATE customers SET total_debt = :d WHERE id = :cid")
                ->execute([':d' => $newDebt, ':cid' => $id]);

            // Heal sales remaining amounts based on total payments received
            $allSales = $pdo->prepare("SELECT id, final_amount FROM sales WHERE customer_id = :cid ORDER BY due_date ASC, created_at ASC");
            $allSales->execute([':cid' => $id]);
            
            $pool = $totalPayments;
            foreach ($allSales->fetchAll() as $s) {
                $final = (float) $s['final_amount'];
                if ($pool >= $final) {
                    $paid = $final;
                    $pool -= $final;
                } else {
                    $paid = $pool;
                    $pool = 0;
                }
                $remaining = $final - $paid;
                $pdo->prepare("UPDATE sales SET paid_amount = :p, remaining_amount = :r WHERE id = :sid")
                    ->execute([':p' => $paid, ':r' => $remaining, ':sid' => $s['id']]);
            }
            $pdo->commit();
            setFlash('success', 'Customer balance and sales statuses repaired by scanning all records.');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', $e->getMessage());
        }
        redirect(BASE_URL . '/modules/customers/detail.php?id=' . $id);
    } elseif ($action === 'edit_payment') {
        $pid = (int) post('payment_id');
        $amt = (float) post('amount');
        $method = post('method');
        $note = post('note');

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = :pid AND customer_id = :cid");
            $stmt->execute([':pid' => $pid, ':cid' => $id]);
            $oldP = $stmt->fetch();
            if ($oldP) {
                $diff = $amt - $oldP['amount'];
                $pdo->prepare("UPDATE customers SET total_debt = total_debt - :diff WHERE id = :cid")->execute([':diff' => $diff, ':cid' => $id]);
                if ($oldP['sale_id']) {
                    $pdo->prepare("UPDATE sales SET paid_amount = paid_amount + :diff1, remaining_amount = remaining_amount - :diff2 WHERE id = :sid")
                        ->execute([':diff1' => $diff, ':diff2' => $diff, ':sid' => $oldP['sale_id']]);
                }
                $pdo->prepare("UPDATE payments SET amount = :a, method = :m, note = :n WHERE id = :i")
                    ->execute([':a' => $amt, ':m' => $method, ':n' => $note, ':i' => $pid]);
                $pdo->commit();
                setFlash('success', __('success'));
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', $e->getMessage());
        }
        redirect(BASE_URL . '/modules/customers/detail.php?id=' . $id);

    } elseif ($action === 'edit_sale') {
        $sid = (int) post('sale_id');
        $note = post('note');
        $due_date = post('due_date') ?: null;

        $pdo->prepare("UPDATE sales SET note = :n, due_date = :d WHERE id = :i AND customer_id = :cid")
            ->execute([':n' => $note, ':d' => $due_date, ':i' => $sid, ':cid' => $id]);
        setFlash('success', __('success'));
        redirect(BASE_URL . '/modules/customers/detail.php?id=' . $id);
    }
}

// İstatistik & Listeler...
$sales = $pdo->prepare("
    SELECT s.*, 
           (
               SELECT GROUP_CONCAT(CONCAT(p.name, ' <i>(x', si.quantity, ')</i>') SEPARATOR '<br>')
               FROM sale_items si 
               JOIN products p ON si.product_id = p.id 
               WHERE si.sale_id = s.id
           ) as items_summary,
           (SELECT SUM(quantity) FROM sale_items WHERE sale_id = s.id) as item_count 
    FROM sales s
    WHERE s.customer_id = :cid 
    ORDER BY s.created_at DESC
");
$sales->execute([':cid' => $id]);
$sales = $sales->fetchAll();
$payments = $pdo->query("SELECT * FROM payments WHERE customer_id = $id AND amount > 0 ORDER BY created_at DESC")->fetchAll();

// ── Geçmişleri Birleştir & Sırala ────────────
$history = [];
foreach ($sales as $s) {
    $history[] = [
        '_type' => 'sale',
        '_time' => strtotime($s['created_at']),
        'data' => $s
    ];
}
foreach ($payments as $p) {
    $history[] = [
        '_type' => 'payment',
        '_time' => strtotime($p['created_at']),
        'data' => $p
    ];
}
usort($history, function ($a, $b) {
    return $b['_time'] <=> $a['_time']; // Yeniden eskiye
});

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

    <div class="col-12">
        <div class="panel">
            <div class="panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i
                        class="bi bi-clock-history me-2"></i><?= __('transaction_history') ?? 'İşlem Geçmişi' ?>
                    <span class="badge bg-secondary ms-2" style="font-size:12px;"
                        id="recordCountBadge"><?= count($history) ?></span>
                </h5>
                <!-- Dinamik Arama & Tarih Filtresi -->
                <div class="d-flex gap-2">
                    <input type="text" id="histSearchBox" class="form-control-dark form-control-sm"
                        placeholder="<?= __('search_placeholder') ?>" style="width: 200px;">
                    <input type="date" id="histDateStart" class="form-control-dark form-control-sm"
                        style="width: 130px;" title="<?= __('start_date') ?>">
                    <input type="date" id="histDateEnd" class="form-control-dark form-control-sm" style="width: 130px;"
                        title="<?= __('end_date') ?>">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table-dark-custom align-middle" id="historyTable">
                    <thead>
                        <tr>
                            <th><?= __('date') ?></th>
                            <th><?= __('type') ?></th>
                            <th><?= __('description') ?></th>
                            <th class="text-end"><?= __('amount_paid') ?></th>
                            <th class="text-end"><?= __('status') ?> / <?= __('remaining') ?></th>
                            <th class="text-end"><?= __('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted"><?= __('no_data') ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $row):
                                $isSale = ($row['_type'] === 'sale');
                                $item = $row['data'];
                                ?>
                                <?php if ($isSale): ?>
                                    <!-- SATIŞ VEYA BORÇ SATIRI -->
                                    <tr class="hist-row <?= $item['remaining_amount'] > 0 ? 'row-low' : '' ?>"
                                        data-date="<?= date('Y-m-d', strtotime($item['created_at'])) ?>">
                                        <td style="font-size:12px; width:130px;">
                                            <?= date('d.m.Y H:i', strtotime($item['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?php if ($item['item_count'] > 0): ?>
                                                <span class="badge"
                                                    style="background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid #ef4444;">
                                                    <i class="bi bi-cart3 me-1"></i><?= __('sale') ?? 'Satış' ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge"
                                                    style="background:rgba(14,165,233,0.1);color:#0ea5e9;border:1px solid #0ea5e9;">
                                                    <i class="bi bi-journal-plus me-1"></i><?= __('debt_note') ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size:13px; max-width:250px;">
                                            <div><strong class="text-muted">#<?= $item['id'] ?> <?= __('transaction_no') ?></strong></div>
                                            <?php if ($item['item_count'] > 0): ?>
                                                <div class="mt-1" style="font-size:11px;color:var(--text-muted); line-height:1.4;">
                                                    <?= $item['items_summary'] ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted"><i
                                                        class="bi bi-info-circle me-1"></i><?= e($item['note'] ?: __('manual_debt_added')) ?></span>
                                            <?php endif; ?>

                                            <?php if ($item['due_date'] && $item['remaining_amount'] > 0):
                                                $days = (int) ceil((strtotime($item['due_date']) - time()) / 86400);
                                                $color = $days < 0 ? 'var(--danger)' : ($days <= 7 ? 'var(--warning)' : 'var(--text-muted)');
                                                ?>
                                                <div style="font-size:11px; color:<?= $color ?>; margin-top:4px;">
                                                    <i class="bi bi-calendar-event me-1"></i><?= __('due') ?>:
                                                    <?= date('d.m.Y', strtotime($item['due_date'])) ?>
                                                    (<?= $days < 0 ? abs($days) . ' ' . __('late') : $days . ' ' . __('left') ?>)
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-bold" style="color:var(--text-primary);">
                                                <?= formatMoney((float) $item['final_amount']) ?>
                                            </div>
                                            <?php if ($item['paid_amount'] > 0): ?>
                                                <div style="font-size:11px; color:var(--success);"><?= __('paid') ?>:
                                                    <?= formatMoney((float) $item['paid_amount']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($item['remaining_amount'] > 0): ?>
                                                <strong
                                                    style="color:var(--danger);"><?= formatMoney((float) $item['remaining_amount']) ?></strong>
                                            <?php else: ?>
                                                <span class="badge bg-success-soft text-success px-2 py-1"><i
                                                        class="bi bi-check-circle me-1"></i><?= __('paid') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn-sm-icon text-info me-1" title="<?= __('view') ?>"
                                                onclick="viewTransaction('sale', <?= $item['id'] ?>)"><i
                                                    class="bi bi-eye"></i></button>
                                            <a href="<?= BASE_URL ?>/modules/sales/edit.php?id=<?= $item['id'] ?>"
                                                class="btn-sm-icon text-warning me-1" title="<?= __('edit') ?>"><i
                                                    class="bi bi-pencil"></i></a>

                                            <?php if ($item['invoice_path']): ?>
                                                <a href="<?= BASE_URL ?>/storage/invoices/<?= e($item['invoice_path']) ?>"
                                                    target="_blank" class="btn-sm-icon me-1"><i class="bi bi-file-earmark-pdf"></i></a>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL ?>/modules/sales/invoice.php?id=<?= $item['id'] ?>"
                                                    target="_blank" class="btn-sm-icon me-1" title="Fatura"><i
                                                        class="bi bi-printer"></i></a>
                                            <?php endif; ?>
                                            <form method="POST" action="detail.php?id=<?= $id ?>"
                                                onsubmit="return confirm('<?= __('confirm_delete') ?>')"
                                                style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="delete_sale">
                                                <input type="hidden" name="sale_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn-sm-icon btn-delete" title="Delete"><i
                                                        class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>

                                <?php else: ?>
                                    <!-- TAHSİLAT VEYA ÖDEME SATIRI -->
                                    <?php [$mlabel, $mcls] = $methodLabels[$item['method']] ?? [$item['method'], 'badge-stock-low']; ?>
                                    <tr class="hist-row" data-date="<?= date('Y-m-d', strtotime($item['created_at'])) ?>">
                                        <td style="font-size:12px; width:130px;">
                                            <?= date('d.m.Y H:i', strtotime($item['created_at'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge"
                                                style="background:rgba(34,197,94,0.1);color:#22c55e;border:1px solid #22c55e;">
                                                <i class="bi bi-cash-stack me-1"></i><?= __('receipt') ?? 'Tahsilat' ?>
                                            </span>
                                        </td>
                                        <td style="font-size:13px; max-width:250px;">
                                            <div><strong class="text-muted">#<?= $item['id'] ?> <?= __('transaction_no') ?></strong></div>
                                            <div><span class="<?= $mcls ?> px-2 py-1 mt-1 d-inline-block"
                                                    style="font-size:10px;"><?= $mlabel ?></span></div>
                                            <?php if ($item['note']): ?>
                                                <div class="text-muted mt-1" style="font-size:11px;"><i
                                                        class="bi bi-info-circle me-1"></i><?= e($item['note']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <strong
                                                style="color:var(--success);"><?= formatMoney((float) $item['amount']) ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-secondary-soft text-secondary px-2 py-1"><i
                                                    class="bi bi-check me-1"></i><?= __('collected') ?></span>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn-sm-icon text-info me-1" title="<?= __('view') ?>"
                                                onclick="viewTransaction('payment', <?= $item['id'] ?>)"><i
                                                    class="bi bi-eye"></i></button>
                                            <button type="button" class="btn-sm-icon text-warning me-1" title="<?= __('edit') ?>"
                                                onclick="editPayment(<?= $item['id'] ?>, <?= $item['amount'] ?>, '<?= $item['method'] ?>', '<?= htmlspecialchars($item['note'] ?? '', ENT_QUOTES) ?>')"><i
                                                    class="bi bi-pencil"></i></button>

                                            <a href="<?= BASE_URL ?>/modules/customers/receipt.php?id=<?= $item['id'] ?>"
                                                target="_blank" class="btn-sm-icon me-1" title="<?= __('receipt') ?>"><i
                                                    class="bi bi-printer"></i></a>
                                            <form method="POST" action="detail.php?id=<?= $id ?>"
                                                onsubmit="return confirm('<?= __('confirm_delete') ?>')"
                                                style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="delete_payment">
                                                <input type="hidden" name="payment_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn-sm-icon btn-delete" title="Delete"><i
                                                        class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>
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

<!-- ── Düzenleme Modalları ─────────────────────────────────────── -->
<div class="modal fade modal-dark" id="editPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Tahsilat Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="detail.php?id=<?= $id ?>" data-once>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="edit_payment">
                <input type="hidden" name="payment_id" id="ep_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('amount') ?> <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control-dark" name="amount" id="ep_amt" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('payment_method') ?></label>
                        <select class="form-select-dark" name="method" id="ep_method">
                            <option value="cash"><?= __('cash') ?></option>
                            <option value="card"><?= __('card') ?></option>
                            <option value="transfer"><?= __('transfer') ?></option>
                            <option value="other"><?= __('other') ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark"><?= __('note') ?></label>
                        <input type="text" class="form-control-dark" name="note" id="ep_note">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"><?= __('cancel') ?></button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check-lg me-1"></i><?= __('update') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTxTitle"><i class="bi bi-search me-2"></i><?= __('transaction_preview') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="viewTxBody" style="overflow-y:auto; max-height:80vh;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('close') ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    // Tablodaki işlemleri dinamik filtreleme
    document.addEventListener('DOMContentLoaded', function () {
        const sBox = document.getElementById('histSearchBox');
        const dStart = document.getElementById('histDateStart');
        const dEnd = document.getElementById('histDateEnd');
        const rows = document.querySelectorAll('.hist-row');
        const badge = document.getElementById('recordCountBadge');

        function applyFilter() {
            const query = sBox.value.toLowerCase();
            const start = dStart.value; // YYYY-MM-DD
            const end = dEnd.value;     // YYYY-MM-DD
            let visibleCount = 0;

            rows.forEach(row => {
                const rowText = row.innerText.toLowerCase();
                const rowDate = row.getAttribute('data-date');

                let showText = rowText.includes(query);
                let showDate = true;

                if (start && rowDate < start) showDate = false;
                if (end && rowDate > end) showDate = false;

                if (showText && showDate) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            badge.textContent = visibleCount;
        }

        if (sBox) sBox.addEventListener('input', applyFilter);
        if (dStart) dStart.addEventListener('change', applyFilter);
        if (dEnd) dEnd.addEventListener('change', applyFilter);
    });

    // AJAX Popup modal fonksiyonu
    function viewTransaction(type, id) {
        document.getElementById('viewTxBody').innerHTML = '<div class="text-center p-5"><div class="spinner-border text-accent" role="status"></div></div>';
        new bootstrap.Modal(document.getElementById('viewTransactionModal')).show();

        fetch('<?= BASE_URL ?>/modules/sales/view_api.php?type=' + type + '&id=' + id)
            .then(r => r.text())
            .then(html => {
                document.getElementById('viewTxBody').innerHTML = html;
                document.getElementById('viewTxTitle').innerText = (type === 'sale' ? 'İşlem/Fatura #' : 'Ödeme/Tahsilat #') + id;
            });
    }

    function editPayment(id, amt, method, note) {
        document.getElementById('ep_id').value = id;
        document.getElementById('ep_amt').value = amt;
        document.getElementById('ep_method').value = method;
        document.getElementById('ep_note').value = note;
        new bootstrap.Modal(document.getElementById('editPaymentModal')).show();
    }
</script>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>