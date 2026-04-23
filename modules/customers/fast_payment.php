<?php
/**
 * Hızlı Tahsilat / Avans Girişi Sayfası
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die('CSRF hatası.');
    }

    $customerId = (int) post('customer_id');
    $amount = (float) post('amount');
    $method = post('method');
    $note = post('note');

    if ($customerId <= 0) {
        setFlash('error', 'Please select a customer.');
    } elseif ($amount <= 0) {
        setFlash('error', 'Payment amount must be greater than 0.');
    } else {
        $pdo->beginTransaction();
        try {
            // Ödeme kaydı oluştur
            $stmt = $pdo->prepare("
                INSERT INTO payments (customer_id, amount, method, note) 
                VALUES (:cid, :amt, :m, :n)
            ");
            $stmt->execute([
                ':cid' => $customerId,
                ':amt' => $amount,
                ':m' => $method,
                ':n' => $note
            ]);
            $receiptId = $pdo->lastInsertId();

            // Müşteri total_debt güncelle (tahsilat/avans borcu azaltır)
            $pdo->prepare("
                UPDATE customers 
                SET total_debt = total_debt - :amt 
                WHERE id = :cid
            ")->execute([':amt' => $amount, ':cid' => $customerId]);

            $pdo->commit();

            $_SESSION['last_payment_receipt_id'] = $receiptId;
            setFlash('success', 'Payment received successfully.');
            redirect(BASE_URL . '/modules/customers/fast_payment.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', 'Error occurred during registration: ' . $e->getMessage());
        }
    }
}

// Müşterileri listele
$customers = $pdo->query("SELECT id, first_name, last_name, unique_id, total_debt FROM customers ORDER BY first_name ASC")->fetchAll();

$preselectedCustomerId = (int) get('customer_id');

$pageTitle = __('fast_payment');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">

        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 style="margin:0;"><i class="bi bi-wallet2 me-2"></i>
                <?= __('txt_hizli_tahsilat') ?>
            </h4>
            <a href="<?= BASE_URL ?>/modules/customers/index.php" class="btn btn-outline-secondary btn-sm"><i
                    class="bi bi-arrow-left me-1"></i>
                <?= __('close') ?>
            </a>
        </div>

        <div class="panel">
            <?php if (isset($_SESSION['last_payment_receipt_id'])):
                $receiptId = $_SESSION['last_payment_receipt_id'];
                unset($_SESSION['last_payment_receipt_id']);
                ?>
                <div class="p-3 mb-3 text-center"
                    style="background:rgba(34,197,94,0.1);border-bottom:1px solid rgba(34,197,94,0.2);">
                    <span class="d-block mb-2" style="color:var(--success);"><i class="bi bi-check-circle me-1"></i>
                        Your last transaction has been reflected in the system.</span>
                    <a href="receipt.php?id=<?= $receiptId ?>" target="_blank" class="btn btn-success btn-sm"><i
                            class="bi bi-printer me-1"></i>
                        <?= __('print_receipt') ?>
                    </a>
                </div>
            <?php endif; ?>

            <div class="panel-body">
                <form method="POST" action="fast_payment.php" data-once>
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                    <div class="mb-4">
                        <label class="form-label-dark">
                            <?= __('txt_musteri_secin') ?><span style="color:#ef9a9a">*</span>
                        </label>
                        <select name="customer_id" id="customer_id" class="form-select-dark" required
                            onchange="showCustomerDebt()">
                            <option value="">
                                <?= __('txt__musteri_ara_sec') ?>
                            </option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>" data-debt="<?= $c['total_debt'] ?>"
                                    <?= $c['id'] === $preselectedCustomerId ? 'selected' : '' ?>>
                                    <?= e($c['first_name'] . ' ' . $c['last_name']) ?> (ID:
                                    <?= e($c['unique_id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="debtDisplay" class="mt-2" style="display:none; font-size:13px;">
                            <?= __('txt_guncel_cari_durum') ?><strong id="debtAmount"
                                style="color:var(--accent);">0.00</strong>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-dark">
                            <?= __('txt_tahsilat_avans_tutari') ?><span style="color:#ef9a9a">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"
                                style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);color:#fff;"><i
                                    class="bi bi-currency-dollar"></i></span>
                            <input type="number" name="amount" class="form-control-dark"
                                style="border-top-left-radius:0;border-bottom-left-radius:0;" step="0.01" min="0.01"
                                data-positive required placeholder="<?= __('txt_orn_50000') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-dark">
                            <?= __('txt_odeme_yontemi') ?>
                        </label>
                        <select name="method" class="form-select-dark">
                            <option value="cash">
                                <?= __('txt_nakit') ?>
                            </option>
                            <option value="card">
                                <?= __('txt_kredibanka_karti') ?>
                            </option>
                            <option value="transfer">
                                <?= __('txt_havaleeft') ?>
                            </option>
                            <option value="other">
                                <?= __('txt_diger') ?>
                            </option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-dark">
                            <?= __('txt_aciklama_not') ?>
                        </label>
                        <input type="text" name="note" class="form-control-dark"
                            placeholder="<?= __('txt_orn_ekim_ayi_avansi_eski_borc') ?>">
                    </div>

                    <hr style="border-color:rgba(255,255,255,0.06);margin:24px 0;">

                    <button type="submit" class="btn-accent w-100 py-2" style="font-size:16px;">
                        <i class="bi bi-check-circle me-2"></i>
                        <?= __('txt_tahsilati_tamamla') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const lang = {
        debtor: "<?= __('debtor') ?>",
        creditor: "<?= __('creditor_advance') ?>",
        no_balance: "<?= __('no_debt') ?>"
    };

    function showCustomerDebt() {
        const sel = document.getElementById('customer_id');
        const display = document.getElementById('debtDisplay');
        const amountSpan = document.getElementById('debtAmount');

        if (sel.selectedIndex <= 0) {
            display.style.display = 'none';
            return;
        }

        const opt = sel.options[sel.selectedIndex];
        const debt = parseFloat(opt.getAttribute('data-debt') || '0');

        display.style.display = 'block';
        if (debt > 0) {
            amountSpan.textContent = debt.toFixed(2) + ' ' + lang.debtor;
            amountSpan.style.color = 'var(--danger)';
        } else if (debt < 0) {
            amountSpan.textContent = Math.abs(debt).toFixed(2) + ' ' + lang.creditor;
            amountSpan.style.color = 'var(--success)';
        } else {
            amountSpan.textContent = lang.no_balance + ' (0.00)';
            amountSpan.style.color = 'var(--text-muted)';
        }
    }

    // Seçim varsa script yüklenince de göster
    window.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('customer_id').selectedIndex > 0) {
            showCustomerDebt();
        }
    });
</script>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>