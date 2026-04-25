<?php
/**
 * New Sale Screen
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$errors = [];

// ── POST: Satışı Kaydet ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'save_sale') {

    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die(__('error'));
    }

    // ── 1. Girdi al ─────────────────────────────────────
    $customerId = post('customer_id') ? (int) post('customer_id') : null;
    $discountType = in_array(post('discount_type'), ['none', 'percent', 'fixed'])
        ? post('discount_type') : 'none';
    $discountValue = max(0, (float) post('discount_value'));
    $paidAmount = max(0, (float) post('paid_amount'));
    $dueDate = post('due_date') ?: null;
    $note = post('note');

    // Sepet kalemleri (JSON olarak gönderilir)
    $cartJson = post('cart_data');
    $cart = json_decode($cartJson, true);

    if (empty($cart) || !is_array($cart)) {
        $errors[] = __('cart_empty_error');
    }

    // ── 2. Stok kontrolleri ─────────────────────────────
    if (empty($errors)) {
        foreach ($cart as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                $errors[] = __('invalid_cart_item');
                break;
            }

            $stmtS = $pdo->prepare("SELECT name, stock_quantity FROM products WHERE id = :pid");
            $stmtS->execute([':pid' => $productId]);
            $p = $stmtS->fetch();

            if (!$p || $p['stock_quantity'] < $qty) {
                $errors[] = __('insufficient_stock_error', $p['name'] ?? '', $p['stock_quantity'] ?? 0);
                break;
            }
        }
    }

    // ── 3. Hesaplama ─────────────────────────────────────
    if (empty($errors)) {

        $totalAmount = 0;
        foreach ($cart as $item) {
            $totalAmount += (float) $item['unit_price'] * (int) $item['quantity'];
        }

        $discountAmount = 0;
        if ($discountType === 'percent') {
            $discountAmount = $totalAmount * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $discountAmount = min($discountValue, $totalAmount);
        }

        $finalAmount = max(0, $totalAmount - $discountAmount);
        $paidAmount = min($paidAmount, $finalAmount); // fazla ödeme olmasın
        $remainingAmount = max(0, $finalAmount - $paidAmount);

        $pdo->beginTransaction();
        try {
            $currency = getCurrentCurrency();

            // 4a. Satış kaydı
            $stmt = $pdo->prepare("
                INSERT INTO sales (customer_id, currency, due_date, total_amount, discount_type, discount_value, final_amount, paid_amount, remaining_amount, note)
                VALUES (:cid, :curr, :due, :tot, :dtype, :dval, :fin, :paid, :rem, :note)
            ");
            $stmt->execute([
                ':cid' => $customerId,
                ':curr' => $currency,
                ':due' => $dueDate,
                ':tot' => $totalAmount,
                ':dtype' => $discountType,
                ':dval' => $discountValue,
                ':fin' => $finalAmount,
                ':paid' => $paidAmount,
                ':rem' => $remainingAmount,
                ':note' => $note
            ]);
            $saleId = $pdo->lastInsertId();

            // 4b. Satış kalemleri ve Stok Düşme
            $stmtItem = $pdo->prepare("
                INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price)
                VALUES (:sid, :pid, :qty, :up, :tp)
            ");
            $stmtStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - :q WHERE id = :pid");

            // Satın alma fiyatını çekmek için
            $stmtCost = $pdo->prepare("SELECT purchase_price FROM products WHERE id = :pid");

            foreach ($cart as $item) {
                $pid = (int) $item['product_id'];
                $q = (int) $item['quantity'];
                $up = (float) $item['unit_price'];

                $tp = $up * $q;

                $stmtItem->execute([
                    ':sid' => $saleId,
                    ':pid' => $pid,
                    ':qty' => $q,
                    ':up' => $up,
                    ':tp' => $tp
                ]);

                $stmtStock->execute([':q' => $q, ':pid' => $pid]);
            }

            // Log record
            logAction('Sale', __('sale_log_completed', $saleId, $finalAmount));

            // 4c. If there is a collection, record payment
            if ($paidAmount > 0) {
                $stmtPay = $pdo->prepare("
                    INSERT INTO payments (customer_id, sale_id, amount, method, note)
                    VALUES (:cid, :sid, :amt, 'cash', :n)
                ");
                $stmtPay->execute([
                    ':cid' => $customerId,
                    ':sid' => $saleId,
                    ':amt' => $paidAmount,
                    ':n' => __('downpayment_note')
                ]);
            }

            // Müşteri ise bakiyeyi güncelle (kalan tutarı borç yaz)
            if ($customerId > 0 && $remainingAmount > 0) {
                $pdo->prepare("UPDATE customers SET total_debt = total_debt + :rem WHERE id = :cid")
                    ->execute([':rem' => $remainingAmount, ':cid' => $customerId]);
            }

            // Sipariş/Satış notu veya genel bilgi
            if (!empty($note)) {
                // Şimdilik sadece ödemeye not atılabilir ya da sales tablosuna note sütunu eklenebilir.
                // Biz payments'a attık peşin ise, değilse ileride tabloya eklenebilir.
            }

            $pdo->commit();
            setFlash('success', __('sale_completed_success', $saleId));
            redirect(BASE_URL . '/modules/sales/index.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = __('error') . ': ' . $e->getMessage();
        }
    }
}

// ── GET: Form Verileri ─────────────────────────────────
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$pageTitle = __('new_sale');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li>
                    <?= e($err) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" action="new.php" id="saleForm">
    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="action" value="save_sale">
    <input type="hidden" name="cart_data" id="cartData">

    <div class="row g-4">

        <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         SOL KOLON: Ürün Arama + Sepet
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
        <div class="col-lg-7">

            <!-- Ürün Arama -->
            <div class="panel mb-4">
                <div class="panel-header">
                    <h5><i class="bi bi-search me-2"></i>
                        <?= __('add_product_to_sale') ?>
                    </h5>
                </div>
                <div class="panel-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <input type="text" id="productSearch" class="form-control-dark"
                                placeholder="<?= __('search_drug_placeholder') ?>" autocomplete="new-password">
                        </div>
                        <div class="col-md-4">
                            <select id="categoryFilter" class="form-select-dark">
                                <option value="">
                                    <?= __('all_categories') ?>
                                </option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>">
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="stockFilter" class="form-select-dark">
                                <option value="">
                                    <?= __('stock_status') ?>
                                </option>
                                <option value="available">
                                    <?= __('in_stock') ?>
                                </option>
                                <option value="out">
                                    <?= __('out_of_stock') ?>
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Ürün Arama Sonuçları -->
                    <div id="productResults" style="max-height:320px;overflow-y:auto;border:1px solid rgba(255,255,255,0.08);
                            border-radius:10px;display:none;">
                    </div>
                </div>
            </div>

            <!-- Sepet -->
            <div class="panel">
                <div class="panel-header">
                    <h5><i class="bi bi-cart3 me-2"></i>
                        <?= __('cart') ?><span id="cartCount" class="badge bg-secondary ms-2">0</span>
                    </h5>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                        <i class="bi bi-trash me-1"></i>
                        <?= __('clear_cart') ?>
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table-dark-custom" id="cartTable">
                        <thead>
                            <tr>
                                <th>
                                    <?= __('medicine_item') ?>
                                </th>
                                <th>
                                    <?= __('unit_price') ?>
                                </th>
                                <th>
                                    <?= __('quantity') ?>
                                </th>
                                <th>
                                    <?= __('total') ?>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cartBody">
                            <tr id="emptyCartRow">
                                <td colspan="5" class="text-center py-5" style="color:var(--text-muted);">
                                    <i class="bi bi-cart" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                                    <?= __('cart_is_empty') ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /SOL -->

        <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         SAĞ KOLON: Müşteri + Ödeme
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
        <div class="col-lg-5">

            <!-- Müşteri Seç -->
            <div class="panel mb-4">
                <div class="panel-header">
                    <h5><i class="bi bi-person me-2"></i>
                        <?= __('customer') ?>
                    </h5>
                </div>
                <div class="panel-body">

                    <div class="mb-3" id="customerSearchBox">
                        <label class="form-label-dark">
                            <?= __('search_customer') ?>
                        </label>
                        <input type="text" id="customerSearch" class="form-control-dark"
                            placeholder="<?= __('search_customer_placeholder') ?>" autocomplete="new-password">
                        <div id="customerResults" style="position:relative;z-index:99;background:#162333;
                                border:1px solid rgba(255,255,255,0.1);border-radius:8px;
                                margin-top:4px;display:none;max-height:200px;overflow-y:auto;">
                        </div>
                    </div>

                    <!-- Seçili müşteri -->
                    <div id="selectedCustomerBox" style="display:none;" class="mb-3">
                        <div class="p-3"
                            style="background:rgba(14,165,233,0.08);border:1px solid rgba(14,165,233,0.2);border-radius:10px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong id="selCustomerName">—</strong>
                                    <div style="font-size:12px;color:var(--text-muted);" id="selCustomerPhone">
                                    </div>
                                    <div style="font-size:12px;color:var(--danger);" id="selCustomerDebt"></div>
                                </div>
                                <button type="button" class="btn-sm-icon btn-delete" onclick="clearCustomer()"
                                    title="<?= __('delete') ?>">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="customer_id" id="customerId">

                    <!-- Hızlı Müşteri Ekle -->
                    <div id="quickAddCustomer">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" data-bs-toggle="collapse"
                            data-bs-target="#quickCustomerForm">
                            <i class="bi bi-person-plus me-1"></i>
                            <?= __('quick_add_customer') ?>
                        </button>
                        <div class="collapse mt-3" id="quickCustomerForm">
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="text" id="quickFirstName" class="form-control-dark"
                                        placeholder="<?= __('first_name') ?>">
                                </div>
                                <div class="col-6">
                                    <input type="text" id="quickLastName" class="form-control-dark"
                                        placeholder="<?= __('last_name') ?>">
                                </div>
                                <div class="col-12">
                                    <input type="text" id="quickPhone" class="form-control-dark"
                                        placeholder="<?= __('phone') ?>">
                                </div>
                                <div class="col-12">
                                    <button type="button" class="btn-accent w-100" onclick="quickAddCustomer()">
                                        <i class="bi bi-check-lg me-1"></i>
                                        <?= __('add_and_select') ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- İskonto & Ödeme -->
            <div class="panel">
                <div class="panel-header">
                    <h5><i class="bi bi-calculator me-2"></i>
                        <?= __('payment_summary') ?>
                    </h5>
                </div>
                <div class="panel-body">

                    <!-- İskonto -->
                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('discount') ?>
                        </label>
                        <div class="d-flex gap-2">
                            <select name="discount_type" id="discountType" class="form-select-dark"
                                style="max-width:130px;" onchange="recalculate()">
                                <option value="none">
                                    <?= __('none') ?>
                                </option>
                                <option value="percent">
                                    <?= __('discount_pct') ?>
                                </option>
                                <option value="fixed">
                                    <?= __('discount_fixed') ?>
                                </option>
                            </select>
                            <input type="number" name="discount_value" id="discountValue" class="form-control-dark"
                                min="0" step="0.01" placeholder="0" value="0" oninput="recalculate()">
                        </div>
                    </div>

                    <!-- Özet Kutusu -->
                    <div class="mb-3" style="background:rgba(0,0,0,0.2);border-radius:10px;padding:16px;">
                        <div class="d-flex justify-content-between mb-2" style="font-size:14px;">
                            <span style="color:var(--text-muted);">
                                <?= __('subtotal') ?>
                            </span>
                            <span id="summarySubtotal">0,00 ₺</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2" style="font-size:14px;">
                            <span style="color:var(--text-muted);">
                                <?= __('discount') ?>
                            </span>
                            <span id="summaryDiscount" style="color:var(--warning);">—</span>
                        </div>
                        <hr style="border-color:rgba(255,255,255,0.08);margin:10px 0;">
                        <div class="d-flex justify-content-between" style="font-size:18px;font-weight:700;">
                            <span>
                                <?= __('net_total') ?>
                            </span>
                            <span id="summaryTotal" style="color:var(--accent);">0,00 ₺</span>
                        </div>
                    </div>

                    <!-- Ödeme -->
                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('paid_amount') ?>
                        </label>
                        <input type="number" name="paid_amount" id="paidAmount" class="form-control-dark" min="0"
                            step="0.01" placeholder="0.00" value="0" oninput="recalculate()">
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1"
                                onclick="payFull()">
                                <?= __('pay_all') ?>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1"
                                onclick="payNone()">
                                <?= __('on_credit') ?>
                            </button>
                        </div>
                    </div>

                    <!-- Kalan -->
                    <div class="mb-4 p-3" style="background:rgba(239,68,68,0.07);border-radius:10px;display:none;"
                        id="remainingBox">
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-size:13px;color:var(--text-muted);">
                                <?= __('remaining_debt') ?>
                            </span>
                            <strong id="summaryRemaining" style="color:var(--danger);font-size:18px;">0,00
                                ₺</strong>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
                            <?= __('will_be_added_to_debt') ?>
                        </div>
                    </div>

                    <!-- Ödeme Vadesi -->
                    <div class="mb-3" id="dueDateBox" style="display:none;">
                        <label class="form-label-dark"><?= __('due_date') ?></label>
                        <input type="date" name="due_date" id="dueDate" class="form-control-dark" value="">
                        <div class="text-muted" style="font-size:11px;margin-top:4px;">
                            <?= __('due_date_help') ?>
                        </div>
                    </div>

                    <!-- Not -->
                    <div class="mb-4">
                        <label class="form-label-dark">
                            <?= __('note') ?>
                        </label>
                        <input type="text" name="note" class="form-control-dark"
                            placeholder="<?= __('sale_note_placeholder') ?>">
                    </div>

                    <button type="button" class="btn-accent w-100" style="padding:14px;font-size:16px;"
                        onclick="submitSale()">
                        <i class="bi bi-check-circle me-2"></i>
                        <?= __('complete_sale') ?>
                    </button>

                </div>
            </div>

        </div><!-- /SAĞ -->

    </div><!-- /.row -->
</form>

<script>
    // --- 1. Sepet Yönetimi ---
    let cart = [];
    let currencySymbol = "<?= getCurrencySymbol() ?>";

    function addToCart(product) {
        // Sepette var mı?
        const existing = cart.find(i => i.product_id === product.id);
        if (existing) {
            if (existing.quantity < product.stock_quantity) {
                existing.quantity++;
            } else {
                alert("<?= __('max_stock_reached') ?>".replace('%d', product.stock_quantity));
                return;
            }
        } else {
            if (product.stock_quantity <= 0) {
                if (!confirm("<?= __('product_out_of_stock_confirm') ?>")) return;
            }
            cart.push({
                product_id: product.id,
                name: product.name,
                unit: product.unit,
                unit_price: parseFloat(product.sale_price),
                quantity: 1,
                stock_quantity: parseInt(product.stock_quantity),
            });
        }
        renderCart();
        recalculate();
    }

    /**
     * Sepetten ürün kaldır
     */
    function removeFromCart(idx) {
        cart.splice(idx, 1);
        renderCart();
        recalculate();
    }

    /**
     * Adet değiştir
     */
    function changeQty(idx, val) {
        const newQty = parseInt(val);
        if (newQty <= 0) { removeFromCart(idx); return; }
        if (newQty > cart[idx].stock_quantity) {
            alert('<?= __('max_stock_reached_alert') ?>: ' + cart[idx].stock_quantity);
            cart[idx].quantity = cart[idx].stock_quantity;
        } else {
            cart[idx].quantity = newQty;
        }
        renderCart();
        recalculate();
    }

    /**
     * Fiyat değiştir (serbest fiyat girişi için)
     */
    function changePrice(idx, val) {
        const newPrice = parseFloat(val);
        if (newPrice < 0) return;
        cart[idx].unit_price = newPrice;
        renderCart();
        recalculate();
    }

    /**
     * Sepeti boşalt
     */
    function clearCart() {
        if (!confirm("<?= __('clear_cart_confirm') ?>")) return;
        cart = [];
        renderCart();
        recalculate();
    }

    /**
     * Ekrana çiz
     */
    function renderCart() {
        const tbody = document.getElementById('cartBody');
        const emptyRow = document.getElementById('emptyCartRow');
        document.getElementById('cartCount').textContent = cart.length;

        if (cart.length === 0) {
            tbody.innerHTML = '';
            tbody.appendChild(emptyRow);
            return;
        }

        let html = '';
        cart.forEach((item, idx) => {
            const total = item.unit_price * item.quantity;
            html += `
        <tr>
            <td>
                <strong style="font-size:13px;">${item.name}</strong>
                <div style="font-size:11px;color:var(--text-muted);"><?= __('stock') ?>: ${item.stock_quantity} ${item.unit}</div>
            </td>
            <td>
                <input type="number" class="form-control-dark"
                       style="width:100px;padding:6px 10px;font-size:13px;"
                       value="${item.unit_price.toFixed(2)}"
                       min="0" step="0.01"
                       onchange="changePrice(${idx}, this.value)">
            </td>
            <td>
                <input type="number" id="qty_${idx}"
                       class="form-control-dark"
                       style="width:80px;padding:6px 10px;font-size:13px;"
                       value="${item.quantity}" min="1"
                       onchange="changeQty(${idx}, this.value)">
            </td>
            <td>
                <strong>${currencySymbol}${total.toFixed(2)}</strong>
            </td>
            <td>
                <button type="button" class="btn-sm-icon btn-delete" onclick="removeFromCart(${idx})">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    /**
     * Toplamları hesapla
     */
    function recalculate() {
        const subtotal = cart.reduce((s, i) => s + (i.unit_price * i.quantity), 0);

        const dType = document.getElementById('discountType').value;
        const dVal = parseFloat(document.getElementById('discountValue').value) || 0;

        let discountAmt = 0;
        if (dType === 'percent') {
            discountAmt = subtotal * (dVal / 100);
        } else if (dType === 'fixed') {
            discountAmt = dVal;
        }

        const finalTotal = Math.max(0, subtotal - discountAmt);

        document.getElementById('summarySubtotal').textContent = currencySymbol + subtotal.toFixed(2);

        if (discountAmt > 0) {
            document.getElementById('summaryDiscount').textContent = `-${currencySymbol}${discountAmt.toFixed(2)}`;
        } else {
            document.getElementById('summaryDiscount').textContent = '—';
        }

        document.getElementById('summaryTotal').textContent = currencySymbol + finalTotal.toFixed(2);

        // Kalan (Borç) kısmı
        const paid = parseFloat(document.getElementById('paidAmount').value) || 0;
        const remBox = document.getElementById('remainingBox');
        const remSpan = document.getElementById('summaryRemaining');

        if (paid < finalTotal && finalTotal > 0) {
            const rem = finalTotal - paid;
            remBox.style.display = 'block';
            remSpan.textContent = currencySymbol + rem.toFixed(2);
        } else {
            remBox.style.display = 'none';
        }
    }

    function payFull() {
        // İndirim sonrası net tutarı bulup paidAmount'a yaz
        const subtotal = cart.reduce((s, i) => s + (i.unit_price * i.quantity), 0);
        const dType = document.getElementById('discountType').value;
        const dVal = parseFloat(document.getElementById('discountValue').value) || 0;
        let discountAmt = 0;
        if (dType === 'percent') { discountAmt = subtotal * (dVal / 100); }
        else if (dType === 'fixed') { discountAmt = dVal; }

        const finalTotal = Math.max(0, subtotal - discountAmt);
        document.getElementById('paidAmount').value = finalTotal.toFixed(2);
        recalculate();
    }

    function payNone() {
        document.getElementById('paidAmount').value = '0';
        recalculate();
    }

    // --- 2. Ürün Arama ---
    let searchTimer = null;
    document.getElementById('productSearch').addEventListener('input', triggerSearch);
    document.getElementById('categoryFilter').addEventListener('change', triggerSearch);
    document.getElementById('stockFilter').addEventListener('change', triggerSearch);

    function triggerSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(fetchProducts, 300);
    }

    function fetchProducts() {
        const q = document.getElementById('productSearch').value.trim();
        const cat = document.getElementById('categoryFilter').value;
        const stock = document.getElementById('stockFilter').value;
        const box = document.getElementById('productResults');

        if (q.length < 1 && cat === '' && stock === '') { box.style.display = 'none'; return; }

        const url = `<?= BASE_URL ?>/modules/sales/product_search_api.php?q=${encodeURIComponent(q)}&cat=${cat}&stock=${stock}`;
        fetch(url)
            .then(r => r.json())
            .then(res => {
                box.style.display = 'block';
                if (!res.success || res.data.length === 0) {
                    box.innerHTML = `<div style="padding:16px;color:var(--text-muted);text-align:center;"><?= __('product_not_found') ?></div>`;
                } else {
                    let html = '';
                    res.data.forEach(p => {
                        html += `
                    <div onclick='addToCart(${JSON.stringify(p).replace(/'/g, "&#39;")})'
                         style="padding:12px 16px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,0.05);
                                display:flex;justify-content:space-between;align-items:center;
                                transition:background 0.15s;"
                         onmouseover="this.style.background='rgba(14,165,233,0.1)'"
                         onmouseout="this.style.background=''">
                        <div>
                            <strong style="font-size:13px;">${p.name}</strong>
                            <div style="font-size:11px;color:var(--text-muted);">${p.dosage_form || ''} ${p.category_name ? '• ' + p.category_name : ''}</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:14px;font-weight:600;color:var(--accent);">${currencySymbol}${parseFloat(p.sale_price).toFixed(2)}</div>
                            <div style="font-size:11px;color:${p.stock_quantity <= 0 ? 'var(--danger)' : p.stock_quantity <= p.critical_stock ? 'var(--warning)' : 'var(--success)'};"><?= __('stock') ?>: ${p.stock_quantity} ${p.unit}</div>
                        </div>
                    </div>
                        `;
                    });
                    box.innerHTML = html;
                }
            });
    }

    // Modal / sayfa dışı tıklamada arama sonuçlarını kapatmak
    document.addEventListener('click', (e) => {
        const pBox = document.getElementById('productResults');
        if (pBox && !e.target.closest('.col-lg-7') && e.target.id !== 'productSearch') {
            pBox.style.display = 'none';
        }

        const cBox = document.getElementById('customerResults');
        if (cBox && !e.target.closest('#customerSearchBox')) {
            cBox.style.display = 'none';
        }
    });

    // --- 3. Müşteri Arama ---
    let custTimer = null;
    document.getElementById('customerSearch').addEventListener('input', function () {
        clearTimeout(custTimer);
        const val = this.value.trim();
        const box = document.getElementById('customerResults');
        if (val.length < 2) { box.style.display = 'none'; return; }

        custTimer = setTimeout(() => {
            fetch(`<?= BASE_URL ?>/modules/customers/search_api.php?q=${encodeURIComponent(val)}`)
                .then(r => r.json())
                .then(res => {
                    box.style.display = 'block';
                    if (!res.success || res.data.length === 0) {
                        box.innerHTML = `<div style="padding:12px;color:var(--text-muted);font-size:13px;"><?= __('no_data') ?></div>`;
                    } else {
                        let html = '';
                        res.data.forEach(c => {
                            html += `
                        <div onclick="selectCustomer(${c.id}, '${c.full_name.replace(/'/g, "\\'")}', '${(c.phone || '').replace(/'/g, "\\'")}', ${c.total_debt}, ${c.payment_due_days})"
                             style="padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid rgba(255,255,255,0.05);"
                             onmouseover="this.style.background='rgba(14,165,233,0.1)'"
                             onmouseout="this.style.background=''">
                            <strong>${c.full_name}</strong> ${c.phone ? `<span style="color:var(--text-muted);margin-left:8px;">${c.phone}</span>` : ''}
                            ${c.total_debt > 0 ? `<span style="color:var(--danger);float:right;font-size:12px;"><?= __('debt') ?>: ${parseFloat(c.total_debt).toFixed(2)}</span>` : ''}
                        </div>
                            `;
                        });
                        box.innerHTML = html;
                    }
                });
        }, 300);
    });

    function selectCustomer(id, name, phone, debt, dueDays = 30) {
        document.getElementById('customerId').value = id;
        document.getElementById('selCustomerName').textContent = name;
        document.getElementById('selCustomerPhone').textContent = phone || '—';

        const debtSpan = document.getElementById('selCustomerDebt');
        if (debt > 0) {
            debtSpan.textContent = `<?= __('total_debt') ?>: ${currencySymbol}${parseFloat(debt).toFixed(2)}`;
            debtSpan.style.color = 'var(--danger)';
        } else if (debt < 0) {
            debtSpan.textContent = `<?= __('creditor_advance') ?>: ${currencySymbol}${Math.abs(debt).toFixed(2)}`;
            debtSpan.style.color = 'var(--success)';
        } else {
            debtSpan.textContent = '';
        }

        // Vade Tarihi Hesaplama
        const dueDateBox = document.getElementById('dueDateBox');
        const dueDateInput = document.getElementById('dueDate');
        if (id > 0) {
            dueDateBox.style.display = 'block';
            const today = new Date();
            today.setDate(today.getDate() + parseInt(dueDays));
            dueDateInput.value = today.toISOString().split('T')[0];
        } else {
            dueDateBox.style.display = 'none';
        }

        document.getElementById('selectedCustomerBox').style.display = 'block';
        document.getElementById('customerSearchBox').style.display = 'none';
        document.getElementById('customerResults').style.display = 'none';
        document.getElementById('quickAddCustomer').style.display = 'none';
    }

    function clearCustomer() {
        document.getElementById('customerId').value = '';
        document.getElementById('selectedCustomerBox').style.display = 'none';
        document.getElementById('customerSearchBox').style.display = 'block';
        document.getElementById('customerSearch').value = '';
        document.getElementById('quickAddCustomer').style.display = 'block';
    }

    // --- 4. Hızlı Müşteri Ekle ---
    function quickAddCustomer() {
        const fn = document.getElementById('quickFirstName').value.trim();
        const ln = document.getElementById('quickLastName').value.trim();
        const ph = document.getElementById('quickPhone').value.trim();

        if (fn === '' || ln === '') {
            alert('<?= __('first_last_name_required') ?>');
            return;
        }

        const fd = new FormData();
        fd.append('first_name', fn);
        fd.append('last_name', ln);
        fd.append('phone', ph);
        fd.append('csrf_token', '<?= e($_SESSION['csrf_token']) ?>');

        fetch(`<?= BASE_URL ?>/modules/customers/quick_add_api.php`, {
            method: 'POST',
            body: fd
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    selectCustomer(res.customer.id, res.customer.full_name, res.customer.phone, 0);
                } else {
                    alert(res.error || '<?= __('customer_add_error') ?>');
                }
            })
            .catch(err => {
                console.error(err);
                alert('<?= __('network_error_generic') ?>');
            });
    }

    // --- 5. Form Submit (Satışı Tamamla) ---
    function submitSale() {
        if (cart.length === 0) {
            alert('<?= __('cart_empty_error') ?>');
            return;
        }

        const custId = document.getElementById('customerId').value;
        const paid = parseFloat(document.getElementById('paidAmount').value) || 0;

        const subtotal = cart.reduce((s, i) => s + (i.unit_price * i.quantity), 0);
        const dType = document.getElementById('discountType').value;
        const dVal = parseFloat(document.getElementById('discountValue').value) || 0;
        let discountAmt = 0;
        if (dType === 'percent') discountAmt = subtotal * (dVal / 100);
        else if (dType === 'fixed') discountAmt = dVal;

        const finalTotal = Math.max(0, subtotal - discountAmt);

        if (!custId && paid < finalTotal && finalTotal > 0) {
            alert('<?= __('on_credit_customer_required_error') ?>');
            return;
        }

        document.getElementById('cartData').value = JSON.stringify(cart);
        // Formu gönder
        document.getElementById('saleForm').submit();
    }
</script>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>