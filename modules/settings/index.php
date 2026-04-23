<?php
/**
 * Preferences
 */
require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die(__('error'));
    }
    
    // Dil, Para Birimi ve Tema
    $lang = post('language');
    $cur  = post('currency');
    $theme = post('theme');
    
    if ($lang) setLang($lang);
    if ($cur)  setCurrency($cur);
    if ($theme) {
        $customColors = [];
        if ($theme === 'custom') {
            $customColors = [
                'body'    => post('custom_body'),
                'sidebar' => post('custom_sidebar'),
                'card'    => post('custom_card'),
                'accent'  => post('custom_accent'),
                'text'    => post('custom_text')
            ];
        }
        setTheme($theme, $customColors);
    }
    
    // Logo ve Placeholder Yükleme
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
        move_uploaded_file($_FILES['site_logo']['tmp_name'], dirname(__DIR__, 2) . '/storage/images/logo.png');
    }
    if (isset($_FILES['default_product_img']) && $_FILES['default_product_img']['error'] === UPLOAD_ERR_OK) {
        move_uploaded_file($_FILES['default_product_img']['tmp_name'], dirname(__DIR__, 2) . '/storage/images/placeholder.png');
    }

    setFlash('success', __('settings_saved'));
    redirect('index.php');
}

$curLangCode = getCurrentLang();
$curCurCode = getCurrentCurrency();
$curTheme = getCurrentTheme();
$customColors = json_decode($_COOKIE['custom_theme_colors'] ?? '{}', true) ?: [];

$themes = getThemes();

$pageTitle = __('preferences');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<div class="row g-4 justify-content-center">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-gear me-2"></i><?= __('preferences') ?></h5>
            </div>
            <div class="panel-body">
                <form method="POST" action="index.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                    <!-- Dil Seçimi -->
                    <div class="mb-4">
                        <label class="form-label-dark"><?= __('select_language') ?></label>
                        <div class="d-flex gap-3 flex-wrap">
                            <?php foreach (getLangMeta() as $code => $meta): ?>
                            <label class="lang-card <?= $code === $curLangCode ? 'active' : '' ?>" style="cursor:pointer;padding:16px;background:rgba(14,165,233,<?= $code === $curLangCode ? '0.1' : '0.03' ?>);border:2px solid <?= $code === $curLangCode ? 'var(--accent)' : 'rgba(255,255,255,0.06)' ?>;border-radius:14px;text-align:center;">
                                <input type="radio" name="language" value="<?= $code ?>"
                                       <?= $code === $curLangCode ? 'checked' : '' ?> style="display:none;">
                                <div style="font-size:32px;margin-bottom:6px;"><?= $meta['flag'] ?></div>
                                <div style="font-size:14px;font-weight:600;"><?= $meta['name'] ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Para Birimi Seçimi -->
                    <div class="mb-4">
                        <label class="form-label-dark"><?= __('select_currency') ?></label>
                        <select name="currency" class="form-select-dark" style="font-size:15px;padding:12px;">
                            <?php foreach (getCurrencyList() as $code => $ci): ?>
                                <option value="<?= $code ?>" <?= $code === $curCurCode ? 'selected' : '' ?>>
                                <?= $ci['symbol'] ?> <?= $code ?> — <?= $ci['name'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-2" style="font-size:12px;color:var(--text-muted);">
                            <?= __('default_currency') ?>: <strong style="color:var(--accent);"><?= getCurrencySymbol() ?> <?= $curCurCode ?></strong>
                        </div>
                    </div>

                    <hr style="border-color:rgba(255,255,255,0.06);">
                    
                    <!-- Tema Seçimi -->
                    <div class="mb-4">
                        <label class="form-label-dark"><?= __('ui_theme') ?></label>
                        <select name="theme" id="themeSelect" class="form-select-dark" style="font-size:15px;padding:12px;">
                            <?php foreach ($themes as $tKey => $tData): ?>
                                <option value="<?= $tKey ?>" <?= $tKey === $curTheme ? 'selected' : '' ?>>
                                <?= $tData['name'] ?>
                            </option>
                            <?php endforeach; ?>
                            <option value="custom" <?= 'custom' === $curTheme ? 'selected' : '' ?>><?= __('custom_colors') ?></option>
                        </select>
                        
                        <div id="customColorsBox" class="mt-3 p-3" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.08);border-radius:12px; <?= 'custom' === $curTheme ? '' : 'display:none;' ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label-dark" style="font-size:12px;"><?= __('background_color') ?></label>
                                    <input type="color" name="custom_body" class="form-control form-control-color w-100" value="<?= $customColors['body'] ?? '#0f1e2d' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-dark" style="font-size:12px;"><?= __('sidebar_color') ?></label>
                                    <input type="color" name="custom_sidebar" class="form-control form-control-color w-100" value="<?= $customColors['sidebar'] ?? '#0d1b2a' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-dark" style="font-size:12px;"><?= __('panel_background') ?></label>
                                    <input type="color" name="custom_card" class="form-control form-control-color w-100" value="<?= $customColors['card'] ?? '#162333' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-dark" style="font-size:12px;"><?= __('accent_color') ?></label>
                                    <input type="color" name="custom_accent" class="form-control form-control-color w-100" value="<?= $customColors['accent'] ?? '#0ea5e9' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-dark" style="font-size:12px;"><?= __('text_color') ?></label>
                                    <input type="color" name="custom_text" class="form-control form-control-color w-100" value="<?= $customColors['text'] ?? '#e2e8f0' ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        document.getElementById('themeSelect').addEventListener('change', function() {
                            document.getElementById('customColorsBox').style.display = this.value === 'custom' ? 'block' : 'none';
                        });
                    </script>

                    <hr style="border-color:rgba(255,255,255,0.06);">
                    
                    <!-- Görsel Ayarları -->
                    <div class="mb-4">
                        <label class="form-label-dark"><?= __('site_logo') ?></label>
                        <div class="d-flex align-items-center gap-3">
                            <?php if(file_exists(dirname(__DIR__,2).'/storage/images/logo.png')): ?>
                                <img src="<?= BASE_URL ?>/storage/images/logo.png?v=<?= time() ?>" alt="Logo" style="width:40px;height:40px;object-fit:contain;">
                            <?php else: ?>
                                <span style="font-size:30px;">🏪</span>
                            <?php endif; ?>
                            <input type="file" name="site_logo" class="form-control-dark custom-file" accept="image/*">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-dark"><?= __('default_product_image') ?></label>
                        <div class="d-flex align-items-center gap-3">
                            <?php if(file_exists(dirname(__DIR__,2).'/storage/images/placeholder.png')): ?>
                                <img src="<?= BASE_URL ?>/storage/images/placeholder.png?v=<?= time() ?>" alt="Placeholder" style="width:40px;height:40px;object-fit:contain;">
                            <?php else: ?>
                                <span style="font-size:30px;background:rgba(255,255,255,0.05);padding:0 8px;border-radius:6px;">📦</span>
                            <?php endif; ?>
                            <input type="file" name="default_product_img" class="form-control-dark custom-file" accept="image/*">
                        </div>
                    </div>

                    <hr style="border-color:rgba(255,255,255,0.06);">

                    <button type="submit" class="btn-accent w-100" style="padding:14px;font-size:15px;">
                        <i class="bi bi-check-lg me-2"></i><?= __('save') ?>
                    </button>
                </form>

                <hr style="border-color:rgba(255,255,255,0.06);margin:24px 0;">

                <!-- Para Birimi Yönetimi Linki -->
                <a href="currencies.php" class="d-block p-3" style="background:rgba(14,165,233,0.06);border:1px solid rgba(14,165,233,0.15);border-radius:12px;text-decoration:none;color:#e2e8f0;transition:all .2s;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-currency-exchange me-2" style="color:var(--accent);font-size:18px;"></i>
                            <strong><?= __('currency_management') ?></strong>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?= __('currency_management_help') ?></div>
                        </div>
                        <i class="bi bi-chevron-right" style="color:var(--accent);"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.lang-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.lang-card').forEach(c => {
                c.classList.remove('active');
                c.style.borderColor = 'rgba(255,255,255,0.06)';
                c.style.background = 'rgba(14,165,233,0.03)';
            });
            this.classList.add('active');
            this.style.borderColor = 'var(--accent)';
            this.style.background = 'rgba(14,165,233,0.1)';
        });
    });
</script>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>
