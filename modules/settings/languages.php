<?php
/**
 * Language and Translation Management - Three Languages Side-by-Side
 * 
 * - Lists all translation keys in the database.
 * - Allows side-by-side editing of TR, EN, FR languages.
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$langs = ['tr', 'en', 'fr'];

// ── AKSİYONLAR ───────────────────────────────────────

// 1. Kaydet (Tüm diller için)
if (post('action') === 'save') {
    $data = $_POST['trans'] ?? []; // [key => [tr => val, en => val, fr => val]]
    $stmt = $pdo->prepare("INSERT INTO translations (lang_code, string_key, string_value) VALUES (:l, :k, :v) ON DUPLICATE KEY UPDATE string_value = :v2");

    foreach ($data as $key => $values) {
        foreach ($langs as $lCode) {
            $val = $values[$lCode] ?? '';
            $stmt->execute([':l' => $lCode, ':k' => $key, ':v' => $val, ':v2' => $val]);
        }
    }
    setFlash('success', __('translations_saved'));
    redirect("languages.php?g=" . get('g', 'all'));
}

// 2. CSV Dışa Aktar (Sadece TR için - Basit tutalım veya tüm dilleri içeren geniş bir csv yapalım)
// Kullanıcı tek dil csv istediğinde l parametresi hala işe yarayabilir
if (get('action') === 'export') {
    $lExport = get('l', 'tr');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=pharmacy_translations_' . $lExport . '_' . date('Ymd') . '.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, ['Key', 'Value']);
    $allTrans = loadTranslations($lExport);
    ksort($allTrans);
    foreach ($allTrans as $k => $v) {
        fputcsv($output, [$k, $v]);
    }
    fclose($output);
    exit;
}

// 3. Dosyaları Tara
if (get('action') === 'scan') {
    $foundKeys = [];
    $dir = new RecursiveDirectoryIterator(dirname(__DIR__, 2));
    $iterator = new RecursiveIteratorIterator($dir);
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            preg_match_all("/__\(\s*['\"]([^'\"]+)['\"]\s*[\),]*/U", $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $mk) {
                    $foundKeys[$mk] = true;
                }
            }
        }
    }
    $added = 0;
    $stmt = $pdo->prepare("INSERT IGNORE INTO translations (lang_code, string_key, string_value) VALUES (:l, :k, :v)");
    foreach (array_keys($foundKeys) as $fk) {
        foreach ($langs as $l) {
            $stmt->execute([':l' => $l, ':k' => $fk, ':v' => $fk]);
            $added += $stmt->rowCount();
        }
    }
    setFlash('success', sprintf(__('scan_completed'), $added));
    redirect("languages.php?g=" . get('g', 'all'));
}

// ── VERİ HAZIRLAMA ────────────────────────────────────
$groups = [
    'all' => __('all_texts'),
    'sidebar' => __('sidebar_menu'),
    'dashboard' => __('dashboard_panel'),
    'products' => __('products_module'),
    'customers' => __('customers_module'),
    'sales' => __('sales_invoice_module'),
    'stock' => __('stock_ops_module'),
    'reports' => __('reports_module'),
    'settings' => __('settings_module'),
    'common' => __('common_texts')
];
$activeGroup = get('g', 'all');

// Tüm dillerin çevirilerini önceden yükle
$allData = [];
foreach ($langs as $l) {
    $allData[$l] = loadTranslations($l);
}

// Tüm Anahtarları Topla
$allKeys = [];
foreach ($langs as $l) {
    $f = dirname(__DIR__, 2) . "/core/lang/{$l}.php";
    if (file_exists($f)) {
        $arr = require $f;
        if (is_array($arr))
            $allKeys = array_merge($allKeys, array_keys($arr));
    }
}
$dbKeys = $pdo->query("SELECT DISTINCT string_key FROM translations")->fetchAll(PDO::FETCH_COLUMN);
$allKeys = array_unique(array_merge($allKeys, $dbKeys));
sort($allKeys);

// Filtreleme
$filteredKeys = [];
foreach ($allKeys as $key) {
    $match = 'common';
    if (str_starts_with($key, 'menu_') || in_array($key, ['receivables_schedule', 'backup_restore', 'report_settings', 'languages_translations', 'user_management', 'dashboard']))
        $match = 'sidebar';
    elseif (str_contains($key, 'chart') || str_contains($key, 'today_') || str_contains($key, 'monthly_') || str_contains($key, 'stat_') || in_array($key, ['six_month_trend']))
        $match = 'dashboard';
    elseif (str_starts_with($key, 'product') || str_starts_with($key, 'categor') || in_array($key, ['barcode', 'sku', 'dosage_form']))
        $match = 'products';
    elseif (str_starts_with($key, 'customer') || in_array($key, ['phone', 'address', 'due_days', 'debt', 'total_debt']))
        $match = 'customers';
    elseif (str_starts_with($key, 'sale') || in_array($key, ['cart', 'discount', 'subtotal', 'net_total', 'paid_amount', 'remaining', 'invoice']))
        $match = 'sales';
    elseif (str_starts_with($key, 'stock') || str_contains($key, 'movement') || str_contains($key, 'entry') || str_contains($key, 'convert'))
        $match = 'stock';
    elseif (str_contains($key, 'report') || str_contains($key, 'log_') || in_array($key, ['revenue', 'profit', 'margin']))
        $match = 'reports';
    elseif (str_contains($key, 'setting') || in_array($key, ['language', 'currency', 'appearance', 'preferences', 'user_management']))
        $match = 'settings';

    if ($activeGroup === 'all' || $activeGroup === $match) {
        $filteredKeys[] = $key;
    }
}

$pageTitle = __('language_management');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="panel">
            <div class="panel-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 overflow-auto pb-1"
                    style="white-space: nowrap; max-width: 70%;">
                    <span class="text-muted small"><i class="bi bi-filter me-1"></i><?= __('filter') ?>:</span>
                    <?php foreach ($groups as $gKey => $gLabel): ?>
                        <a href="?g=<?= $gKey ?>"
                            class="btn btn-sm <?= $activeGroup === $gKey ? 'btn-accent' : 'btn-outline-secondary' ?> py-1 px-3"
                            style="font-size: 12px; border-radius: 20px;">
                            <?= $gLabel ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex gap-2">
                    <a href="?action=scan&g=<?= $activeGroup ?>" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-search me-1"></i><?= __('scan_new_texts') ?>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button"
                            data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i><?= __('excel_csv') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="?action=export&l=tr"><?= __('download_tr') ?></a></li>
                            <li><a class="dropdown-item" href="?action=export&l=en"><?= __('download_en') ?></a></li>
                            <li><a class="dropdown-item" href="?action=export&l=fr"><?= __('download_fr') ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="languages.php?g=<?= $activeGroup ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <div class="panel">
        <div class="panel-header d-flex justify-content-between align-items-center bg-dark">
            <h5 class="mb-0"><i class="bi bi-translate me-2 text-accent"></i><?= $groups[$activeGroup] ?>
            </h5>
            <button type="submit" class="btn btn-accent btn-sm shadow-sm">
                <i class="bi bi-save me-1"></i><?= __('save_all_changes') ?>
            </button>
        </div>
        <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead class="sticky-top bg-dark" style="z-index: 10;">
                    <tr>
                        <th style="width: 20%; background: #0f172a;"><?= __('lang_key') ?></th>
                        <th style="background: #0f172a;">🇹🇷 <?= __('tr_lang') ?></th>
                        <th style="background: #0f172a;">🇺🇸 <?= __('en_lang') ?></th>
                        <th style="background: #0f172a;">🇫🇷 <?= __('fr_lang') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filteredKeys)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted"><?= __('no_text_in_module') ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($filteredKeys as $key): ?>
                        <tr>
                            <td class="font-monospace small text-muted px-3" title="<?= e($key) ?>">
                                <?= e(strlen($key) > 30 ? substr($key, 0, 27) . '...' : $key) ?>
                            </td>
                            <td>
                                <input type="text" name="trans[<?= e($key) ?>][tr]"
                                    class="form-control form-control-sm bg-dark border-secondary text-white"
                                    value="<?= e($allData['tr'][$key] ?? '') ?>" placeholder="TR...">
                            </td>
                            <td>
                                <input type="text" name="trans[<?= e($key) ?>][en]"
                                    class="form-control form-control-sm bg-dark border-secondary text-white"
                                    value="<?= e($allData['en'][$key] ?? '') ?>" placeholder="EN...">
                            </td>
                            <td>
                                <input type="text" name="trans[<?= e($key) ?>][fr]"
                                    class="form-control form-control-sm bg-dark border-secondary text-white"
                                    value="<?= e($allData['fr'][$key] ?? '') ?>" placeholder="FR...">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="panel-footer bg-dark p-3 text-end border-top border-secondary">
            <button type="submit" class="btn btn-accent px-4 py-2">
                <i class="bi bi-save me-2"></i><?= __('apply_changes') ?>
            </button>
        </div>
    </div>
</form>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>