<?php
/**
 * Edit Sale Screen
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$id = (int) get('id');

$stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    setFlash('error', __('not_found'));
    redirect('index.php');
}

$errors = [];

// ── POST: Satışı Güncelle ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'update_sale') {

    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die(__('error'));
    }

    $customerId = post('customer_id') ? (int) post('customer_id') : null;
    $discountType = in_array(post('discount_type'), ['none', 'percent', 'fixed']) ? post('discount_type') : 'none';
    $discountValue = max(0, (float) post('discount_value'));
    $paidAmount = max(0, (float) post('paid_amount'));
    $dueDate = post('due_date') ?: null;
    $note = post('note');

    $cartJson = post('cart_data');
    $cart = json_decode($cartJson, true);

    if (empty($cart) || !is_array($cart)) {
        $errors[] = __('cart_empty_error');
    }

    // Stok Kontrol (Eski miktar + mevcut stok >= yeni miktar)
    if (empty($errors)) {
        foreach ($cart as $item) {
            $pid = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0) {
                $errors[] = __('invalid_cart_item');
                break;
            }

            // Bu satışın önceden harcadığı ürün miktarını bul
            $oldStmt = $pdo->prepare("SELECT quantity FROM sale_items WHERE sale_id = ? AND product_id = ?");
            $oldStmt->execute([$id, $pid]);
            $oldQ = $oldStmt->fetchColumn() ?: 0;

            $stmtS = $pdo->prepare("SELECT name, stock_quantity FROM products WHERE id = ?");
            $stmtS->execute([$pid]);
            $p = $stmtS->fetch();

            // Eğer yeni istenen miktar, eski çektiği miktar + eldekinden büyükse yetersizdir
            if (!$p || ($p['stock_quantity'] + $oldQ) < $qty) {
                $errors[] = __('insufficient_stock_error', $p['name'] ?? '', $p['stock_quantity'] ?? 0);
                break;
            }
        }
    }

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
        $paidAmount = min($paidAmount, $finalAmount);
        $remainingAmount = max(0, $finalAmount - $paidAmount);

        $pdo->beginTransaction();
        try {
            // 1. REVERT: Eski satıştan kaynaklı müşteri borcunu geri al
            if ($sale['customer_id'] > 0 && $sale['remaining_amount'] > 0) {
                $pdo->prepare("UPDATE customers SET total_debt = total_debt - ? WHERE id = ?")->execute([$sale['remaining_amount'], $sale['customer_id']]);
            }

            // 2. REVERT: Eski stokları geri ekle
            $oldItemsStmt = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
            $oldItemsStmt->execute([$id]);
            foreach ($oldItemsStmt->fetchAll() as $oi) {
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?")->execute([$oi['quantity'], $oi['product_id']]);
            }

            // 3. REVERT: sale_items'ları sil
            $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?")->execute([$id]);

            // 4. APPLY: Satışı Güncelle
            $stmt = $pdo->prepare("
                UPDATE sales SET customer_id=?, due_date=?, total_amount=?, discount_type=?, discount_value=?, final_amount=?, paid_amount=?, remaining_amount=?, note=?
                WHERE id=?
            ");
            $stmt->execute([$customerId, $dueDate, $totalAmount, $discountType, $discountValue, $finalAmount, $paidAmount, $remainingAmount, $note, $id]);

            // 5. APPLY: Yeni sale_items oluştur ve stok düş
            $stmtItem = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $stmtStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

            foreach ($cart as $item) {
                $pid = (int) $item['product_id'];
                $q = (int) $item['quantity'];
                $up = (float) $item['unit_price'];
                $tp = $up * $q;

                $stmtItem->execute([$id, $pid, $q, $up, $tp]);
                $stmtStock->execute([$q, $pid]);
            }

            // 6. APPLY: Yeni borcu müşteriye yansıt
            if ($customerId > 0 && $remainingAmount > 0) {
                $pdo->prepare("UPDATE customers SET total_debt = total_debt + ? WHERE id = ?")->execute([$remainingAmount, $customerId]);
            }

            logAction('Sale', "Sale #$id updated successfully. Total: $finalAmount");

            $pdo->commit();
            setFlash('success', "İşlem / Satış Bilgileri Başarıyla Güncellendi");

            // Redirect based on referer info (detail or index)
            redirect(BASE_URL . '/modules/sales/index.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = __('error') . ': ' . $e->getMessage();
        }
    }
}

// ── GET: Form Verileri & Initial Sepet ─────────────────────────────────
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

// Mevcut Müşteriyi çek
$customerObj = null;
if ($sale['customer_id'] > 0) {
    $cS = $pdo->prepare("SELECT id, first_name, last_name, phone, total_debt FROM customers WHERE id = ?");
    $cS->execute([$sale['customer_id']]);
    $customerObj = $cS->fetch();
}

// Sepetteki kalemleri çek ve JSON olarak encode et
$stmtItems = $pdo->prepare("
    SELECT si.*, p.name, p.unit, p.stock_quantity 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    WHERE si.sale_id = ?
");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

$initial_cart = [];
foreach ($items as $i) {
    $initial_cart[] = [
        'product_id' => (int) $i['product_id'],
        'name' => $i['name'],
        'unit' => $i['unit'],
        'unit_price' => (float) $i['unit_price'],
        'quantity' => (int) $i['quantity'],
        'stock_quantity' => (int) $i['stock_quantity'] + (int) $i['quantity'] // Eski kullanılanı da havuza kat
    ];
}

$pageTitle = "İşlemi Düzenle #$id";
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

<form method="POST" action="edit.php?id=<?= $id ?>" id="saleForm">
    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="action" value="update_sale">
    <input type="hidden" name="cart_data" id="cartData">

    <div class="row g-4">
        <!-- SOL KOLON: Ürün Arama + Sepet -->
        <div class="col-lg-7">
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
                                placeholder="<?= __('search_drug_placeholder') ?>">
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
                    <div id="productResults"
                        style="max-height:320px;overflow-y:auto;border:1px solid rgba(255,255,255,0.08);border-radius:10px;display:none;">
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h5><i class="bi bi-cart3 me-2"></i>
                        <?= __('cart') ?> <span id="cartCount" class="badge bg-secondary ms-2">0</span>
                    </h5>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearCart()"><i
                            class="bi bi-trash me-1"></i>
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
                            <tr id="emptyCartRow" style="display:none;">
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

        <!-- SAĞ KOLON: Müşteri + Ödeme -->
        <div class="col-lg-5">
            <div class="panel mb-4">
                <div class="panel-header">
                    <h5><i class="bi bi-person me-2"></i>
                        <?= __('customer') ?>
                    </h5>
                </div>
                <div class="panel-body">
                    <div class="mb-3" id="customerSearchBox" <?= $customerObj ? 'style="display:none;"' : '' ?>>
                        <label class="form-label-dark">
                            <?= __('search_customer') ?>
                        </label>
                        <input type="text" id="customerSearch" class="form-control-dark"
                            placeholder="<?= __('search_customer_placeholder') ?>">
                        <div id="customerResults"
                            style="position:relative;z-index:99;background:#162333;border:1px solid rgba(255,255,255,0.1);border-radius:8px;margin-top:4px;display:none;max-height:200px;overflow-y:auto;">
                        </div>
                    </div>

                    <div id="selectedCustomerBox" <?= $customerObj ? '' : 'style="display:none;"' ?> class="mb-3">
                        <div class="p-3"
                            style="background:rgba(14,165,233,0.08);border:1px solid rgba(14,165,233,0.2);border-radius:10px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong id="selCustomerName">
                                        <?= e($customerObj['first_name'] ?? '') . ' ' . e($customerObj['last_name'] ?? '') ?>
                                    </strong>
                                    <div style="font-size:12px;color:var(--text-muted);" id="selCustomerPhone">
                                        <?= e($customerObj['phone'] ?? '') ?>
                                    </div>
                                    <div style="font-size:12px;color:var(--danger);" id="selCustomerDebt"></div>
                                </div>
                                <button type="button" class="btn-sm-icon btn-delete" onclick="clearCustomer()"
                                    title="<?= __('delete') ?>"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="customer_id" id="customerId" value="<?= $sale['customer_id'] ?: '' ?>">

                    <div id="quickAddCustomer" <?= $customerObj ? 'style="display:none;"' : '' ?>>
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" data-bs-toggle="collapse"
                            data-bs-target="#quickCustomerForm"><i class="bi bi-person-plus me-1"></i>
                            <?= __('quick_add_customer') ?>
                        </button>
                        <div class="collapse mt-3" id="quickCustomerForm">
                            <!-- Yok  -->
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
                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('discount') ?>
                        </label>
                        <div class="d-flex gap-2">
                            <select name="discount_type" id="discountType" class="form-select-dark"
                                style="max-width:130px;" onchange="recalculate()">
                                <option value="none" <?= $sale['discount_type'] == 'none' ? 'selected' : '' ?>>
                                    <?= __('none') ?>
                                </option>
                                <option value="percent" <?= $sale['discount_type'] == 'percent' ? 'selected' : '' ?>>
                                    <?= __('discount_pct') ?>
                                </option>
                                <option value="fixed" <?= $sale['discount_type'] == 'fixed' ? 'selected' : '' ?>>
                                    <?= __('discount_fixed') ?>
                                </option>
                            </select>
                            <input type="number" name="discount_value" id="discountValue" class="form-control-dark"
                                min="0" step="0.01" value="<?= (float) $sale['discount_value'] ?>"
                                oninput="recalculate()">
                        </div>
                    </div>

                    <div class="mb-3" style="background:rgba(0,0,0,0.2);border-radius:10px;padding:16px;">
                        <div class="d-flex justify-content-between mb-2" style="font-size:14px;">
                            <span style="color:var(--text-muted);">
                                <?= __('subtotal') ?>
                            </span>
                            <span id="summarySubtotal">0,00</span>
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
                            <span id="summaryTotal" style="color:var(--accent);">0,00</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('paid_amount') ?>
                        </label>
                        <input type="number" name="paid_amount" id="paidAmount" class="form-control-dark" min="0"
                            step="0.01" value="<?= (float) $sale['paid_amount'] ?>" oninput="recalculate()">
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

                    <div class="mb-4 p-3" style="background:rgba(239,68,68,0.07);border-radius:10px;display:none;"
                        id="remainingBox">
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-size:13px;color:var(--text-muted);">
                                <?= __('remaining_debt') ?>
                            </span>
                            <strong id="summaryRemaining" style="color:var(--danger);font-size:18px;">0,00</strong>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
                            <?= __('will_be_added_to_debt') ?>
                        </div>
                    </div>

                    <div class="mb-3" id="dueDateBox" <?= $sale['due_date'] ? '' : 'style="display:none;"' ?>>
                        <label class="form-label-dark">
                            <?= __('due_date') ?>
                        </label>
                        <input type="date" name="due_date" id="dueDate" class="form-control-dark"
                            value="<?= e($sale['due_date']) ?>">
                        <div class="text-muted" style="font-size:11px;margin-top:4px;">
                            <?= __('due_date_help') ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-dark">
                            <?= __('note') ?>
                        </label>
                        <input type="text" name="note" class="form-control-dark" value="<?= e($sale['note']) ?>">
                    </div>

                    <div class="row g-2">
                        <div class="col-sm-5">
                            <a href="javascript:history.back()" class="btn btn-outline-secondary w-100"
                                style="padding:14px;font-size:16px;">
                                <i class="bi bi-arrow-left me-2"></i>Vazgeç
                            </a>
                        </div>
                        <div class="col-sm-7">
                            <button type="button" class="btn-accent w-100" style="padding:14px;font-size:16px;"
                                onclick="submitSale()">
                                <i class="bi bi-check-circle me-2"></i>Güncelle
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div><!-- /SAĞ -->
    </div>
</form>

<script>
    let cart = <?= json_encode($initial_cart) ?>;
    let currencySymbol = "<?= getCurrencySymbol() ?>";

    function addToCart(product) {
        const existing = cart.find(i => i.product_id === product.id);
        if (existing) {
            if (existing.quantity < product.stock_quantity) { existing.quantity++; }
            else { alert("Maksimum stoka ulaştınız!"); return; }
        } else {
            if (product.stock_quantity <= 0) { if (!confirm("Stok yok, eklensin mi?")) return; }
            cart.push({
                product_id: product.id, name: product.name, unit: product.unit,
                unit_price: parseFloat(product.sale_price), quantity: 1, stock_quantity: parseInt(product.stock_quantity)
            });
        }
        renderCart(); recalculate();
    }

    function removeFromCart(idx) { cart.splice(idx, 1); renderCart(); recalculate(); }

    function changeQty(idx, val) {
        const newQty = parseInt(val);
        if (newQty <= 0) { removeFromCart(idx); return; }
        if (newQty > cart[idx].stock_quantity) {
            alert('Maks stok: ' + cart[idx].stock_quantity); cart[idx].quantity = cart[idx].stock_quantity;
        } else { cart[idx].quantity = newQty; }
        renderCart(); recalculate();
    }

    function changePrice(idx, val) {
        const newPrice = parseFloat(val);
        if (newPrice < 0) return;
        cart[idx].unit_price = newPrice;
        renderCart(); recalculate();
    }

    function clearCart() { if (confirm("Sepeti temizle?")) { cart = []; renderCart(); recalculate(); } }

    function renderCart() {
        const tbody = document.getElementById('cartBody');
        const emptyRow = document.getElementById('emptyCartRow');
        document.getElementById('cartCount').textContent = cart.length;

        if (cart.length === 0) { tbody.innerHTML = ''; tbody.appendChild(emptyRow); emptyRow.style.display = ''; return; }

        let html = '';
        cart.forEach((item, idx) => {
            const total = item.unit_price * item.quantity;
            html += `<tr>
                <td><strong style="font-size:13px;">${item.name}</strong><div style="font-size:11px;color:var(--text-muted);">Maks: ${item.stock_quantity} ${item.unit}</div></td>
                <td><input type="number" class="form-control-dark" style="width:100px;padding:6px 10px;font-size:13px;" value="${item.unit_price.toFixed(2)}" min="0" step="0.01" onchange="changePrice(${idx}, this.value)"></td>
                <td><input type="number" class="form-control-dark" style="width:80px;padding:6px 10px;font-size:13px;" value="${item.quantity}" min="1" onchange="changeQty(${idx}, this.value)"></td>
                <td><strong>${currencySymbol}${total.toFixed(2)}</strong></td>
                <td><button type="button" class="btn-sm-icon btn-delete" onclick="removeFromCart(${idx})"><i class="bi bi-x-lg"></i></button></td>
            </tr>`;
        });
        tbody.innerHTML = html;
    }

    function recalculate() {
        const subtotal = cart.reduce((s, i) => s + (i.unit_price * i.quantity), 0);
        const dType = document.getElementById('discountType').value;
        const dVal = parseFloat(document.getElementById('discountValue').value) || 0;
        let discountAmt = 0;

        if (dType === 'percent') { discountAmt = subtotal * (dVal / 100); }
        else if (dType === 'fixed') { discountAmt = dVal; }

        const finalTotal = Math.max(0, subtotal - discountAmt);
        document.getElementById('summarySubtotal').textContent = currencySymbol + subtotal.toFixed(2);

        if (discountAmt > 0) document.getElementById('summaryDiscount').textContent = `-${currencySymbol}${discountAmt.toFixed(2)}`;
        else document.getElementById('summaryDiscount').textContent = '—';

        document.getElementById('summaryTotal').textContent = currencySymbol + finalTotal.toFixed(2);

        const paid = parseFloat(document.getElementById('paidAmount').value) || 0;
        const remBox = document.getElementById('remainingBox');
        if (paid < finalTotal && finalTotal > 0) {
            remBox.style.display = 'block';
            document.getElementById('summaryRemaining').textContent = currencySymbol + (finalTotal - paid).toFixed(2);
        } else { remBox.style.display = 'none'; }
    }

    function payFull() {
        const subtotal = cart.reduce((s, i) => s + (i.unit_price * i.quantity), 0);
        const dType = document.getElementById('discountType').value;
        const dVal = parseFloat(document.getElementById('discountValue').value) || 0;
        let discountAmt = 0;
        if (dType === 'percent') discountAmt = subtotal * (dVal / 100);
        else if (dType === 'fixed') discountAmt = dVal;
        document.getElementById('paidAmount').value = Math.max(0, subtotal - discountAmt).toFixed(2);
        recalculate();
    }

    function payNone() { document.getElementById('paidAmount').value = '0'; recalculate(); }

    document.getElementById('productSearch').addEventListener('input', function () {
        const q = this.value.trim(); const box = document.getElementById('productResults');
        if (q.length < 2) { box.style.display = 'none'; return; }
        fetch(`<?= BASE_URL ?>/modules/sales/product_search_api.php?q=${encodeURIComponent(q)}`)
            .then(r => r.json()).then(res => {
                box.style.display = 'block';
                if (!res.success || res.data.length === 0) { box.innerHTML = `<div style="padding:16px;text-align:center;">Bulunamadı</div>`; }
                else {
                    let html = '';
                    res.data.forEach(p => {
                        html += `<div onclick='addToCart(${JSON.stringify(p).replace(/'/g, "&#39;")})' style="padding:10px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,0.05);">${p.name} - ${currencySymbol}${parseFloat(p.sale_price).toFixed(2)}</div>`;
                    });
                    box.innerHTML = html;
                }
            });
    });

    function submitSale() {
        if (cart.length === 0) { alert("Sepet boş!"); return; }
        document.getElementById('cartData').value = JSON.stringify(cart);
        document.getElementById('saleForm').submit();
    }

    function clearCustomer() {
        document.getElementById('customerId').value = '';
        document.getElementById('selectedCustomerBox').style.display = 'none';
        document.getElementById('customerSearchBox').style.display = 'block';
        document.getElementById('quickAddCustomer').style.display = 'block';
        document.getElementById('dueDateBox').style.display = 'none';
        document.getElementById('dueDate').value = '';
    }

    // İlk Yüklemede çalıştır
    window.addEventListener('DOMContentLoaded', () => { renderCart(); recalculate(); });
</script>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>