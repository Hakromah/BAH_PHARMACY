<?php
/**
 * View Transaction API (Read-Only Preview)
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';
$pdo = Database::getInstance();

$id = (int) get('id');
$type = get('type') ?: 'sale'; // sale veya payment

// Eğer Tahsilat/Ödeme İşlemi ise:
if ($type === 'payment') {
    $stmt = $pdo->prepare("SELECT p.*, c.first_name, c.last_name FROM payments p LEFT JOIN customers c ON p.customer_id = c.id WHERE p.id = ?");
    $stmt->execute([$id]);
    $pay = $stmt->fetch();
    if (!$pay) {
        echo "<div class='alert alert-danger'>İşlem bulunamadı.</div>";
        exit;
    }

    $methods = [
        'cash' => ['Nakit', 'bi-cash'],
        'card' => ['Kredi Kartı', 'bi-credit-card'],
        'transfer' => ['Havale/EFT', 'bi-bank'],
        'other' => ['Diğer', 'bi-wallet2']
    ];
    $method = $methods[$pay['method']] ?? [$pay['method'], 'bi-cash'];
    ?>
    <div class="p-4" style="background:var(--card-bg); border-radius:12px;">
        <div class="text-center mb-4">
            <div
                style="width:60px;height:60px;background:rgba(34,197,94,0.1);color:#22c55e;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 15px;">
                <i class="bi <?= $method[1] ?>"></i>
            </div>
            <h4 style="margin:0;color:var(--success);">
                <?= formatMoney((float) $pay['amount']) ?>
            </h4>
            <div class="text-muted" style="font-size:13px;margin-top:5px;">Tahsilat / Ödeme İşlemi</div>
        </div>
        <table class="table table-borderless" style="color:var(--text-primary); font-size:14px;">
            <tr>
                <td class="text-muted" style="width:120px;">Müşteri:</td>
                <td class="fw-bold" style="color:var(--text-primary);">
                    <?= e($pay['first_name'] . ' ' . $pay['last_name']) ?></td>
            </tr>
            <tr>
                <td class="text-muted">İşlem Tarihi:</td>
                <td style="color:var(--text-primary);"><?= date('d.m.Y H:i', strtotime($pay['created_at'])) ?></td>
            </tr>
            <tr>
                <td class="text-muted">Ödeme Tipi:</td>
                <td style="color:var(--text-primary);"><?= $method[0] ?></td>
            </tr>
            <tr>
                <td class="text-muted">Açıklama/Not:</td>
                <td style="color:var(--text-primary);"><?= e($pay['note'] ?: 'Belirtilmedi') ?></td>
            </tr>
        </table>
    </div>
    <?php
    exit;
}

// Eğer Satış/Fatura İşlemi ise:
$stmt = $pdo->prepare("SELECT s.*, c.first_name, c.last_name, c.unique_id, c.phone FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = ?");
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) {
    echo "<div class='alert alert-danger'>Satış kaydı bulunamadı.</div>";
    exit;
}

$stmtItems = $pdo->prepare("SELECT si.*, p.name as p_name, p.unit FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

?>
<div class="row g-3">
    <!-- SOL KOLON: SEPET ONAYI -->
    <div class="col-lg-7">
        <div
            style="background:var(--card-bg); border:1px solid var(--border-color); border-radius:10px; overflow:hidden; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
            <div style="padding:15px; border-bottom:1px solid var(--border-color); background:rgba(0,0,0,0.02);">
                <strong style="font-size:14px; color:var(--text-primary);"><i class="bi bi-cart3 me-2"></i>Satış Sepeti
                    Özeti</strong>
            </div>
            <div class="table-responsive">
                <table class="table m-0" style="font-size:13px; color:var(--text-primary);">
                    <thead>
                        <tr>
                            <th>İlaç/Ürün</th>
                            <th class="text-end">Birim Fiyat</th>
                            <th class="text-center">Miktar</th>
                            <th class="text-end">Toplam</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subTotal = 0;
                        foreach ($items as $it):
                            $tot = $it['quantity'] * $it['unit_price'];
                            $subTotal += $tot;
                            ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--text-primary);"><?= e($it['p_name']) ?></strong>
                                </td>
                                <td class="text-end text-muted"><?= formatMoney((float) $it['unit_price']) ?></td>
                                <td class="text-center fw-bold" style="color:var(--text-primary);">x<?= $it['quantity'] ?>
                                </td>
                                <td class="text-end fw-bold" style="color:var(--text-primary);">
                                    <?= formatMoney((float) $tot) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($sale['note'] || $sale['due_date']): ?>
            <div class="mt-3 p-3"
                style="background:var(--card-bg); border-radius:10px; border:1px solid var(--border-color); font-size:13px; color:var(--text-primary);">
                <?php if ($sale['note']): ?>
                    <div class="mb-2"><strong class="text-muted">İşlem Notu:</strong> <br><?= e($sale['note']) ?></div>
                <?php endif; ?>
                <?php if ($sale['due_date']): ?>
                    <div><strong class="text-muted">Vade Tarihi:</strong> <?= date('d.m.Y', strtotime($sale['due_date'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- SAĞ KOLON: MÜŞTERİ VE ÖDEME ÖZETİ -->
    <div class="col-lg-5">
        <?php if ($sale['customer_id'] > 0): ?>
            <div class="mb-3 p-3"
                style="background:rgba(14,165,233,0.08); border:1px solid rgba(14,165,233,0.2); border-radius:10px;">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-person-circle fs-3 me-2 text-info"></i>
                    <div style="line-height:1.2;">
                        <div class="fw-bold" style="font-size:15px; color:var(--text-primary);">
                            <?= e($sale['first_name'] . ' ' . $sale['last_name']) ?></div>
                        <div style="font-size:11px;color:var(--text-muted);"><?= e($sale['phone'] ?: 'Telefonsuz') ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div
            style="background:var(--card-bg); border:1px solid var(--border-color); border-radius:10px; padding:15px; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
            <strong style="font-size:14px;display:block;margin-bottom:15px;color:var(--text-primary);"><i
                    class="bi bi-calculator me-2"></i>Ödeme Özeti</strong>

            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom"
                style="font-size:13px; border-color:var(--border-color)!important;">
                <span class="text-muted">Ara Toplam:</span>
                <span style="color:var(--text-primary);"><?= formatMoney($subTotal) ?></span>
            </div>

            <?php if ($sale['discount_value'] > 0):
                $discAmt = $subTotal - $sale['final_amount'];
                ?>
                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom"
                    style="font-size:13px; border-color:var(--border-color)!important;">
                    <span class="text-muted">İskonto
                        (<?= $sale['discount_type'] == 'percent' ? '%' . $sale['discount_value'] : 'Sabit' ?>):</span>
                    <span class="text-warning">-<?= formatMoney($discAmt) ?></span>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between mb-3" style="font-size:18px; font-weight:700;">
                <span>Net Toplam:</span>
                <span style="color:var(--accent);">
                    <?= formatMoney((float) $sale['final_amount']) ?>
                </span>
            </div>

            <!-- Parçalı Ödeme Bilgisi -->
            <div
                style="background:rgba(0,0,0,0.03); border:1px solid var(--border-color); border-radius:8px; padding:10px; font-size:13px;">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Ödenen (Peşin):</span>
                    <strong class="text-success"><?= formatMoney((float) $sale['paid_amount']) ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Kalan / Açık (Veresiye):</span>
                    <strong class="text-danger"><?= formatMoney((float) $sale['remaining_amount']) ?></strong>
                </div>
            </div>

            <?php if ($sale['remaining_amount'] > 0): ?>
                <div class="mt-2 text-end text-muted" style="font-size:11px;">Müşteri hesabına borç olarak yansıtılmıştır.
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>