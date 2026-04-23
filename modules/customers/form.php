<?php
/**
 * Customer Add / Edit Form
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();

$id = (int) get('id');
$customer = null;
$errors = [];
$dupWarning = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $customer = $stmt->fetch();
    if (!$customer) {
        setFlash('error', 'Customer not found.');
        redirect(BASE_URL . '/modules/customers/index.php');
    }
}

$pageTitle = $id > 0 ? __('edit_customer') : __('new_customer');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die('CSRF token error.');
    }

    $firstName = post('first_name');
    $lastName = post('last_name');
    $phone = post('phone');
    $address = post('address');
    $currency = post('currency', 'USD');
    $dueDays = (int) post('payment_due_days');
    if ($dueDays < 0)
        $dueDays = 30;

    $forceInsert = post('force_insert', '0');

    if (empty($firstName) || empty($lastName)) {
        $errors[] = 'First name and last name fields are required.';
    }

    if (empty($errors)) {
        if ($id == 0 && $forceInsert !== '1') {
            $stmtD = $pdo->prepare("SELECT id, first_name, last_name, phone FROM customers WHERE first_name = :f AND last_name = :l");
            $stmtD->execute([':f' => $firstName, ':l' => $lastName]);
            $duplicates = $stmtD->fetchAll();
            if (count($duplicates) > 0) {
                $dupWarning = $duplicates;
            }
        }

        if (empty($dupWarning)) {
            if ($id > 0) {
                // Güncelle
                $pdo->prepare("
                    UPDATE customers 
                    SET first_name = :f, last_name = :l, phone = :p, address = :a, currency = :cur, payment_due_days = :dd
                    WHERE id = :id
                ")->execute([
                            ':f' => $firstName,
                            ':l' => $lastName,
                            ':p' => $phone,
                            ':a' => $address,
                            ':cur' => $currency,
                            ':dd' => $dueDays,
                            ':id' => $id
                        ]);
                setFlash('success', "{$firstName} {$lastName} updated.");
            } else {
                // Yeni ekle
                $uuid = generateCustomerId($firstName);
                $pdo->prepare("
                    INSERT INTO customers
                        (unique_id, first_name, last_name, phone, address, currency, payment_due_days)
                    VALUES
                        (:uid, :f, :l, :p, :a, :cur, :dd)
                ")->execute([
                            ':uid' => $uuid,
                            ':f' => $firstName,
                            ':l' => $lastName,
                            ':p' => $phone,
                            ':a' => $address,
                            ':cur' => $currency,
                            ':dd' => $dueDays
                        ]);
                setFlash('success', "{$firstName} {$lastName} added.");

                // AJAX ile geldiyse (modal içinden)
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    $newId = $pdo->lastInsertId();
                    echo json_encode(['success' => true, 'id' => $newId, 'name' => "{$firstName} {$lastName}"]);
                    exit;
                }
            }
            redirect(BASE_URL . '/modules/customers/index.php');
        }
    }
}

$currencies = $pdo->query("SELECT * FROM currencies ORDER BY code ASC")->fetchAll();

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

<!-- Aynı isim uyarısı -->
<?php if (!empty($dupWarning)): ?>
    <div class="alert"
        style="background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.35);color:#fde68a;border-radius:12px;padding:18px 22px;">
        <strong><i class="bi bi-exclamation-triangle me-2"></i>
            <?= __('similar_customer_exists') ?>
        </strong>
        <ul class="mb-3 mt-2">
            <?php foreach ($dupWarning as $d): ?>
                <li>
                    <strong>
                        <?= e($d['first_name'] . ' ' . $d['last_name']) ?>
                    </strong>
                    <?php if ($d['phone']): ?> —
                        <?= e($d['phone']) ?>
                    <?php endif; ?>
                    <a href="detail.php?id=<?= $d['id'] ?>" target="_blank"
                        style="color:var(--accent);margin-left:8px;font-size:12px;"><i class="bi bi-box-arrow-up-right"></i>
                        <?= __('examine') ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="mb-3" style="font-size:13px;">Do you want to save it as a new customer?</p>
        <form method="POST" action="form.php">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="first_name" value="<?= e(post('first_name')) ?>">
            <input type="hidden" name="last_name" value="<?= e(post('last_name')) ?>">
            <input type="hidden" name="phone" value="<?= e(post('phone')) ?>">
            <input type="hidden" name="address" value="<?= e(post('address')) ?>">
            <input type="hidden" name="currency" value="<?= e(post('currency')) ?>">
            <input type="hidden" name="payment_due_days" value="<?= e(post('payment_due_days')) ?>">
            <input type="hidden" name="force_insert" value="1">
            <div class="d-flex gap-2">
                <button type="submit" class="btn-accent">
                    <i class="bi bi-check-lg me-1"></i>Yes, Add as New Customer</button>
                <a href="form.php" class="btn btn-outline-secondary btn-sm">
                    <?= __('cancel') ?>
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Form Paneli -->
<?php if (empty($dupWarning)): ?>
    <div class="panel" style="max-width:680px;">
        <div class="panel-header">
            <h5><i class="bi bi-person-<?= $id > 0 ? 'gear' : 'plus' ?> me-2"></i>
                <?= e($pageTitle) ?>
            </h5>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>
                <?= __('back') ?>
            </a>
        </div>
        <div class="panel-body">
            <form method="POST" action="form.php<?= $id > 0 ? '?id=' . $id : '' ?>" data-once>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label-dark">
                            <?= __('first_name') ?><span style="color:#ef9a9a">*</span>
                        </label>
                        <input type="text" name="first_name" class="form-control-dark" required
                            value="<?= e($customer['first_name'] ?? post('first_name')) ?>" placeholder="Ex: John">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-dark">
                            <?= __('last_name') ?><span style="color:#ef9a9a">*</span>
                        </label>
                        <input type="text" name="last_name" class="form-control-dark" required
                            value="<?= e($customer['last_name'] ?? post('last_name')) ?>" placeholder="Ex: Doe">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-dark">
                            <?= __('phone') ?>
                        </label>
                        <input type="text" name="phone" class="form-control-dark"
                            value="<?= e($customer['phone'] ?? post('phone')) ?>" placeholder="0555 000 00 00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-dark">
                            <?= __('currency') ?>
                        </label>
                        <select name="currency" class="form-select-dark">
                            <?php foreach ($currencies as $c): ?>
                                <option value="<?= e($c['code']) ?>" <?= ($customer['currency'] ?? post('currency', 'USD')) === $c['code'] ? 'selected' : '' ?>>
                                    <?= e($c['code']) ?> (
                                    <?= e($c['symbol']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-dark">
                            <?= __('due_days') ?>
                        </label>
                        <input type="number" name="payment_due_days" class="form-control-dark" min="0" max="365"
                            data-positive value="<?= e($customer['payment_due_days'] ?? post('payment_due_days', '30')) ?>">
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Customer's debt will be paid in
                            how many days?</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label-dark">
                            <?= __('address') ?>
                        </label>
                        <textarea name="address" class="form-control-dark" rows="3"
                            placeholder="<?= __('address') ?>"><?= e($customer['address'] ?? post('address')) ?></textarea>
                    </div>

                </div>

                <?php if ($id > 0 && !empty($customer['unique_id'])): ?>
                    <div class="mt-3 p-3" style="background:rgba(0,0,0,0.2);border-radius:8px;">
                        <span style="font-size:12px;color:var(--text-muted);">
                            <i class="bi bi-fingerprint me-1"></i>Customer Profile ID:
                            <code><?= e($customer['unique_id']) ?></code>
                        </span>
                    </div>
                <?php endif; ?>

                <hr style="border-color:rgba(255,255,255,0.08);margin:24px 0;">

                <div class="d-flex gap-3">
                    <button type="submit" class="btn-accent">
                        <i class="bi bi-check-lg me-1"></i>
                        <?= $id > 0 ? __('update') : __('save') ?>
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <?= __('cancel') ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>