<?php
/**
 * Currency and Exchange Rate Management
 *
 * - Add/Edit/Delete currencies
 * - Daily rate entry (1 USD = ?)
 * - View rate history
 * - Parity applies from the set date forward, not retroactively
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';
$pdo = Database::getInstance();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die(__('error'));
    }
    $action = post('action');

    if ($action === 'add_currency') {
        $code = trim(post('code'));
        $symbol = trim(post('symbol'));
        $pos = post('position');
        $name = trim(post('name'));
        $dec = post('decimal_sep');
        $thou = post('thousand_sep');
        $rate = post('initial_rate') !== '' ? (float) post('initial_rate') : null;

        if (strlen($code) < 2 || strlen($code) > 10)
            $errors[] = __('code_length_error');

        if (empty($errors)) {
            $pdo->prepare("INSERT INTO currencies (code, symbol, position, name, decimal_sep, thousand_sep, current_rate, rate_date) VALUES (:c, :s, :p, :n, :d, :t, :r, :rd)")
                ->execute([':c' => $code, ':s' => $symbol, ':p' => $pos, ':n' => $name, ':d' => $dec, ':t' => $thou, ':r' => $rate, ':rd' => ($rate ? date('Y-m-d') : null)]);
            setFlash('success', __('currency_added'));
            redirect('currencies.php');
        }
    } elseif ($action === 'set_rate') {
        $code = post('rate_code');
        $rate = (float) post('rate_value');
        $date = post('rate_date') ?: date('Y-m-d');

        if (strlen($code) < 2 || strlen($code) > 10)
            $errors[] = __('code_length_error');
        if ($rate <= 0)
            $errors[] = __('rate_positive_error');

        if (empty($errors)) {
            $pdo->prepare("INSERT INTO exchange_rates (currency_code, rate_to_usd, effective_date) VALUES (:c, :r, :d) ON DUPLICATE KEY UPDATE rate_to_usd = :r")
                ->execute([':c' => $code, ':r' => $rate, ':d' => $date]);

            // update in currencies
            $pdo->prepare("UPDATE currencies SET current_rate = :r, rate_date = :d WHERE code = :c")->execute([':r' => $rate, ':d' => $date, ':c' => $code]);

            setFlash('success', __('rate_set'));
            redirect('currencies.php');
        }
    } elseif ($action === 'toggle_currency') {
        $tog = post('tog_code');
        $pdo->prepare("UPDATE currencies SET is_active = NOT is_active WHERE code = :c AND is_default = 0")->execute([':c' => $tog]);
        redirect('currencies.php');
    } elseif ($action === 'delete_currency') {
        $del = post('del_code');
        // Check if used in products or sales? FK should handle, but let's be safe
        $pdo->prepare("DELETE FROM currencies WHERE code = :c AND is_default = 0")->execute([':c' => $del]);
        redirect('currencies.php');
    } elseif ($action === 'set_default') {
        $def = post('def_code');
        $pdo->beginTransaction();
        $pdo->query("UPDATE currencies SET is_default = 0");
        $pdo->prepare("UPDATE currencies SET is_default = 1, is_active = 1 WHERE code = :c")->execute([':c' => $def]);
        $pdo->commit();
        redirect('currencies.php');
    }
}

// Data fetching
$currencies = $pdo->query("SELECT * FROM currencies ORDER BY is_active DESC, code ASC")->fetchAll();

$historyCode = get('history');
$rateHistory = [];
if ($historyCode) {
    $stmt = $pdo->prepare("SELECT * FROM exchange_rates WHERE currency_code = :c ORDER BY effective_date DESC LIMIT 30");
    $stmt->execute([':c' => $historyCode]);
    $rateHistory = $stmt->fetchAll();
}

$pageTitle = __('currency');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li>
                    <?= e($err) ?>
                </li>
            <?php endforeach; ?>
        </ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">

    <!-- SOL: Para Birimi Listesi + Kur Tablosu -->
    <div class="col-lg-8">
        <div class="panel mb-4">
            <div class="panel-header">
                <h5><i class="bi bi-currency-exchange me-2"></i>
                    <?= __('currency') ?> —
                    <?= __('all') ?>
                    <span class="badge bg-secondary ms-2">
                        <?= count($currencies) ?>
                    </span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table-dark-custom">
                    <thead>
                        <tr>
                            <th>
                                <?= __('txt_code') ?>
                            </th>
                            <th>
                                <?= __('txt_symbol') ?>
                            </th>
                            <th>
                                <?= __('currency') ?>
                            </th>
                            <th>
                                <?= __('txt_1_usd') ?>
                            </th>
                            <th>
                                <?= __('txt_rate_date') ?>
                            </th>
                            <th>
                                <?= __('status') ?>
                            </th>
                            <th>
                                <?= __('actions') ?>
                            </th>
                            <th>
                                <?= __('set_base') ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currencies as $c):
                            $isUSD = $c['code'] === 'USD';
                            $statusCls = $c['is_active'] ? 'badge-stock-ok' : 'badge-stock-out'; ?>
                            <tr>
                                <td><strong style="font-size:14px;">
                                        <?= e($c['code']) ?>
                                    </strong></td>
                                <td style="font-size:18px;">
                                    <?= e($c['symbol']) ?>
                                </td>
                                <td>
                                    <?= e($c['name']) ?>
                                </td>
                                <td>
                                    <?php if ($c['is_default']): ?>
                                        <span style="color:var(--text-muted);">1.000000 <?= __('set_base') ?></span>
                                    <?php elseif ($c['current_rate']): ?>
                                        <strong style="color:var(--accent);">
                                            <?= number_format($c['current_rate'], 6) ?>
                                        </strong>
                                    <?php else: ?>
                                        <span style="color:var(--danger);">
                                            <?= __('txt__no_rate') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:12px;color:var(--text-muted);">
                                    <?= $c['is_default'] ? '—' : e($c['rate_date']) ?>
                                </td>
                                <td><span class="<?= $statusCls ?>">
                                        <?= $c['is_active'] ? __('active') : __('passive') ?>
                                    </span></td>
                                <td class="d-flex gap-1">
                                    <?php if (!$c['is_default']): ?>
                                        <a href="?history=<?= $c['code'] ?>" class="btn-sm-icon" title="<?= __('history') ?>"><i
                                                class="bi bi-clock-history"></i>
                                        </a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="toggle_currency">
                                            <input type="hidden" name="tog_code" value="<?= e($c['code']) ?>">
                                            <button type="submit" class="btn-sm-icon" title="<?= __('on_off') ?>"
                                                style="color:var(--warning);background:none;border:none;"><i
                                                    class="bi bi-toggle-<?= $c['is_active'] ? 'on' : 'off' ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;"
                                            onsubmit="return confirm('<?= sprintf(__('confirm_delete_currency'), $c['code']) ?>')">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="delete_currency">
                                            <input type="hidden" name="del_code" value="<?= e($c['code']) ?>">
                                            <button type="submit" class="btn-sm-icon btn-delete" title="<?= __('delete') ?>"
                                                style="background:none;border:none;"><i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-info" style="font-size:10px;"><?= __('default_badge') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$c['is_default']): ?>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="set_default">
                                            <input type="hidden" name="def_code" value="<?= e($c['code']) ?>">
                                            <button type="submit" class="btn btn-outline-warning btn-xs"
                                                style="font-size:10px;padding:2px 5px;">
                                                <?= __('set_base') ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <i class="bi bi-star-fill text-warning"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Kur Geçmişi (seçilmişse) -->
        <?php if ($historyCode && !empty($rateHistory)): ?>
            <div class="panel">
                <div class="panel-header">
                    <h5><i class="bi bi-graph-up me-2"></i>
                        <?= __('txt_rate_history') ?>
                        <?= e($historyCode) ?>
                    </h5>
                    <a href="currencies.php" class="btn btn-outline-secondary btn-sm">
                        <?= __('close') ?>
                    </a>
                </div>
                <div class="panel-body" style="height:220px;"><canvas id="rateChart"></canvas></div>
                <div class="table-responsive" style="max-height:300px;">
                    <table class="table-dark-custom">
                        <thead>
                            <tr>
                                <th>
                                    <?= __('txt_date') ?>
                                </th>
                                <th>
                                    <?= __('txt_1_usd') ?>
                                </th>
                                <th>
                                    <?= __('txt_entry_time') ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rateHistory as $rh): ?>
                                <tr>
                                    <td style="font-size:13px;">
                                        <?= date('d.m.Y', strtotime($rh['effective_date'])) ?>
                                    </td>
                                    <td><strong style="color:var(--accent);">
                                            <?= number_format($rh['rate_to_usd'], 6) ?>
                                        </strong></td>
                                    <td style="font-size:12px;color:var(--text-muted);">
                                        <?= date('d.m.Y H:i', strtotime($rh['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
            <script>
                Chart.defaults.color = 'rgba(255, 255, 255, 0.6)';
                Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';
                const rLabels = <?= json_encode(array_reverse(array_map(fn($r) => date('d.m', strtotime($r['effective_date'])), $rateHistory))) ?>;
                const rValues = <?= json_encode(array_reverse(array_map(fn($r) => (float) $r['rate_to_usd'], $rateHistory))) ?>;

                new Chart(document.getElementById('rateChart'), {
                    type: 'line',
                    data: {
                        labels: rLabels,
                        datasets: [{
                            label: '<?= e($historyCode) ?> / 1 USD',
                            data: rValues,
                            borderColor: '#0ea5e9',
                            backgroundColor: 'rgba(14,165,233,0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.2
                        }]
                    },
                    options: { maintainAspectRatio: false }
                });
            </script>
        <?php endif; ?>
    </div>

    <!-- SAĞ: Yeni Para Birimi + Kur Girişi -->
    <div class="col-lg-4">

        <!-- Kur Girişi -->
        <div class="panel mb-4">
            <div class="panel-header">
                <h5><i class="bi bi-graph-up-arrow me-2"></i>
                    <?= __('txt_set_exchange_rate') ?>
                </h5>
            </div>
            <div class="panel-body">
                <form method="POST" action="currencies.php">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="set_rate">

                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('currency') ?>
                        </label>
                        <select name="rate_code" class="form-select-dark" required>
                            <option value="">
                                <?= __('txt__select') ?>
                            </option>
                            <?php foreach ($currencies as $c):
                                if ($c['is_default'])
                                    continue; ?>
                                <option value="<?= e($c['code']) ?>">
                                    <?= e($c['symbol']) ?>
                                    <?= e($c['code']) ?> —
                                    <?= e($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('txt_1_usd') ?>
                        </label>
                        <input type="number" name="rate_value" class="form-control-dark" step="0.000001" min="0.000001"
                            required placeholder="Ex: 3.85" style="font-size:18px;padding:14px;">
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
                            <?= __('txt_enter_how_many_units_of_this_c') ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-dark">
                            <?= __('txt_effective_date') ?>
                        </label>
                        <input type="date" name="rate_date" class="form-control-dark" value="<?= date('Y-m-d') ?>">
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
                            <?= __('txt_rate_applies_from_this_date_fo') ?>
                        </div>
                    </div>

                    <button type="submit" class="btn-accent w-100" style="padding:12px;">
                        <i class="bi bi-check-lg me-2"></i>
                        <?= __('txt_set_rate') ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Yeni Para Birimi Ekle -->
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-plus-circle me-2"></i>
                    <?= __('txt_add_currency') ?>
                </h5>
            </div>
            <div class="panel-body">
                <form method="POST" action="currencies.php">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="add_currency">

                    <div class="row g-3">
                        <div class="col-5">
                            <label class="form-label-dark">
                                <?= __('txt_code') ?>
                            </label>
                            <input type="text" name="code" class="form-control-dark" required placeholder="SAR"
                                maxlength="10" style="text-transform:uppercase;">
                        </div>
                        <div class=" col-3">
                            <label class="form-label-dark">
                                <?= __('txt_symbol') ?>
                            </label>
                            <input type="text" name="symbol" class="form-control-dark" required placeholder="﷼"
                                maxlength="10">
                        </div>
                        <div class=" col-4">
                            <label class="form-label-dark">
                                <?= __('txt_position') ?>
                            </label>
                            <select name="position" class="form-select-dark">
                                <option value="before">$100</option>
                                <option value="after">100₺</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label class="form-label-dark">
                            <?= __('txt_currency_name') ?>
                        </label>
                        <input type="text" name="name" class="form-control-dark" required
                            placeholder="<?= __('txt_saudi_riyal') ?>">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label-dark">
                                <?= __('txt_decimal_sep') ?>
                            </label>
                            <input type="text" name="decimal_sep" class="form-control-dark" value="." maxlength="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label-dark">
                                <?= __('txt_thousand_sep') ?>
                            </label>
                            <input type="text" name="thousand_sep" class="form-control-dark" value="," maxlength="1">
                        </div>
                    </div>

                    <div class="mb-3 p-3"
                        style="background:rgba(14,165,233,0.06);border:1px solid rgba(14,165,233,0.15);border-radius:10px;">
                        <label class="form-label-dark">
                            <?= __('txt_initial_rate_1_usd') ?>
                        </label>
                        <input type="number" name="initial_rate" class="form-control-dark" step="0.000001" min="0"
                            placeholder="<?= __('txt_optional') ?>">
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
                            <?= __('txt_leave_empty_if_youll_set_the_r') ?>
                        </div>
                    </div>

                    <button type="submit" class="btn-accent w-100" style="padding:12px;">
                        <i class="bi bi-plus-lg me-2"></i>
                        <?= __('txt_add_currency') ?>
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>