<?php
/**
 * Layout: Header (Ortak üst kısım)
 * 
 * Tüm modül sayfaları bu dosyayı include eder.
 * $pageTitle değişkeni include öncesinde tanımlanmalıdır.
 * Çok dilli + çok para birimli navbar
 */

$pageTitle = $pageTitle ?? __('app_name');

// Rapor Ayarlarını Çek (Sidebar gizleme kontrolü için)
try {
    $pdo_h = Database::getInstance();
    $rs_val = $pdo_h->query("SELECT value FROM settings WHERE `key` = 'report_settings'")->fetchColumn();
    $rs_header = $rs_val ? json_decode($rs_val, true) : [];
} catch (Exception $e) {
    $rs_header = [];
}

$hideSidebar = $hideSidebar ?? ($rs_header['hide_sidebar'] ?? false);
// Sadece rapor sayfalarında (ledger, invoice vb) otomatik gizle
$isReportPage = str_contains($_SERVER['PHP_SELF'], 'ledger.php') || str_contains($_SERVER['PHP_SELF'], 'invoice.php') || str_contains($_SERVER['PHP_SELF'], 'receipt.php');
if ($isReportPage && $hideSidebar) {
    $sidebarClass = 'collapsed'; // JS ile kontrol edilen sınıf
} else {
    $sidebarClass = '';
}
$flash = getFlash();
$curLang = getCurrentLang();
$langMeta = getLangMeta();
$curCur = getCurrentCurrency();
$curInfo = getCurrencyInfo();
$curFlag = $langMeta[$curLang]['flag'] ?? '🌐';
?>
<!DOCTYPE html>
<html lang="<?= $curLang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= __('app_name') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/app.css">
    <?= getThemeCSS() ?>
</head>

<body>

    <div class="wrapper">
        <nav id="sidebar" class="<?= $sidebarClass ?>">
            <a href="<?= BASE_URL ?>/public/index.php" class="sidebar-brand text-decoration-none">
                <span class="brand-icon">
                    <?php if (file_exists(dirname(__DIR__) . '/storage/images/logo.png')): ?>
                        <img src="<?= BASE_URL ?>/storage/images/logo.png?v=<?= time() ?>" alt="Logo"
                            style="width:32px;height:32px;object-fit:contain;border-radius:4px;">
                    <?php else: ?>
                        💊
                    <?php endif; ?>
                </span>
                <span class="brand-text"><?= __('app_name') ?></span>
            </a>

            <ul class="sidebar-nav">
                <li>
                    <a href="<?= BASE_URL ?>/public/index.php"
                        class="<?= (strpos($_SERVER['PHP_SELF'], 'index.php') !== false && strpos($_SERVER['PHP_SELF'], 'modules') === false) ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i> <?= __('dashboard') ?>
                    </a>
                </li>

                <!-- STOK Grubu -->
                <li>
                    <a href="#menu-stock" data-bs-toggle="collapse"
                        class="d-flex justify-content-between align-items-center <?= (strpos($_SERVER['PHP_SELF'], 'products') !== false || strpos($_SERVER['PHP_SELF'], 'stock') !== false) ? '' : 'collapsed' ?>">
                        <span><i class="bi bi-box-seam me-2"></i><?= __('menu_stock') ?></span>
                        <i class="bi bi-chevron-down small toggle-icon"></i>
                    </a>
                    <ul class="collapse list-unstyled ps-3 <?= (strpos($_SERVER['PHP_SELF'], 'products') !== false || strpos($_SERVER['PHP_SELF'], 'stock') !== false) ? 'show' : '' ?>"
                        id="menu-stock">
                        <li>
                            <a href="<?= BASE_URL ?>/modules/products/index.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'products/index') !== false) ? 'active' : '' ?>">
                                <?= __('products') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/modules/products/categories.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'categories') !== false) ? 'active' : '' ?>">
                                <?= __('categories') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/modules/stock/entry.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'entry') !== false) ? 'active' : '' ?>">
                                <?= __('stock_entry') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/modules/stock/movements.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'movements') !== false) ? 'active' : '' ?>">
                                <?= __('stock_movements') ?>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- MÜŞTERİ & SATIŞ Grubu -->
                <li>
                    <a href="#menu-customers" data-bs-toggle="collapse"
                        class="d-flex justify-content-between align-items-center <?= (strpos($_SERVER['PHP_SELF'], 'customers') !== false || strpos($_SERVER['PHP_SELF'], 'sales') !== false) ? '' : 'collapsed' ?>">
                        <span><i class="bi bi-people me-2"></i><?= __('menu_customer_sales') ?></span>
                        <i class="bi bi-chevron-down small toggle-icon"></i>
                    </a>
                    <ul class="collapse list-unstyled ps-3 <?= (strpos($_SERVER['PHP_SELF'], 'customers') !== false || strpos($_SERVER['PHP_SELF'], 'sales') !== false) ? 'show' : '' ?>"
                        id="menu-customers">
                        <li>
                            <a href="<?= BASE_URL ?>/modules/customers/index.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'customers') !== false) ? 'active' : '' ?>">
                                <?= __('customers') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/modules/sales/index.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'sales/index.php') !== false) ? 'active' : '' ?>">
                                <?= __('sales') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/modules/sales/new.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'sales/new') !== false) ? 'active' : '' ?>">
                                <?= __('new_sale') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/modules/customers/receivables.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'receivables.php') !== false) ? 'active' : '' ?>">
                                <?= __('receivables_schedule') ?>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- RAPORLAR Grubu -->
                <li>
                    <a href="#menu-reports" data-bs-toggle="collapse"
                        class="d-flex justify-content-between align-items-center <?= (strpos($_SERVER['PHP_SELF'], 'reports') !== false) ? '' : 'collapsed' ?>">
                        <span><i class="bi bi-bar-chart-line me-2"></i><?= __('menu_reports') ?></span>
                        <i class="bi bi-chevron-down small toggle-icon"></i>
                    </a>
                    <ul class="collapse list-unstyled ps-3 <?= (strpos($_SERVER['PHP_SELF'], 'reports') !== false) ? 'show' : '' ?>"
                        id="menu-reports">
                        <li>
                            <a href="<?= BASE_URL ?>/modules/reports/index.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'reports/index') !== false) ? 'active' : '' ?>">
                                <?= __('reports') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/modules/reports/logs.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'logs') !== false) ? 'active' : '' ?>">
                                <?= __('action_logs') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/modules/reports/stock_report.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'stock_report') !== false) ? 'active' : '' ?>">
                                <?= __('stock_report') ?>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- AYARLAR Grubu -->
                <li>
                    <a href="#menu-settings" data-bs-toggle="collapse"
                        class="d-flex justify-content-between align-items-center <?= (strpos($_SERVER['PHP_SELF'], 'settings') !== false) ? '' : 'collapsed' ?>">
                        <span><i class="bi bi-gear me-2"></i><?= __('settings') ?></span>
                        <i class="bi bi-chevron-down small toggle-icon"></i>
                    </a>
                    <ul class="collapse list-unstyled ps-3 <?= (strpos($_SERVER['PHP_SELF'], 'settings') !== false) ? 'show' : '' ?>"
                        id="menu-settings">
                        <li>
                            <a href="<?= BASE_URL ?>/modules/settings/index.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'settings/index') !== false) ? 'active' : '' ?>">
                                <?= __('preferences') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/modules/settings/currencies.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'currencies') !== false) ? 'active' : '' ?>">
                                <?= __('currency') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/modules/settings/backup.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'backup') !== false) ? 'active' : '' ?>">
                                <?= __('backup_restore') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/modules/settings/reports.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'reports.php') !== false) ? 'active' : '' ?>">
                                <?= __('report_settings') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/modules/settings/languages.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'languages.php') !== false) ? 'active' : '' ?>">
                                <?= __('languages_translations') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/modules/settings/users.php"
                                class="<?= (strpos($_SERVER['PHP_SELF'], 'users.php') !== false) ? 'active' : '' ?>">
                                <?= __('user_management') ?>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <div class="sidebar-footer">
                <small>v1.2.0 &mdash; <?= date('d.m.Y') ?></small>
            </div>
        </nav>

        <div id="content">
            <!-- Topbar -->
            <div class="topbar">
                <button id="sidebarToggle" class="btn-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="topbar-title"><?= e($pageTitle) ?></div>

                <div class="topbar-right d-flex align-items-center gap-3">

                    <!-- Dil Seçici -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" style="font-size:13px;">
                            <?= $curFlag ?> <?= $langMeta[$curLang]['name'] ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                            <?php foreach ($langMeta as $code => $meta): ?>
                                <li>
                                    <a class="dropdown-item <?= $code === $curLang ? 'active' : '' ?>"
                                        href="?set_lang=<?= $code ?>">
                                        <?= $meta['flag'] ?>     <?= $meta['name'] ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Para Birimi Seçici -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" style="font-size:13px;">
                            <?= $curInfo['symbol'] ?> <?= $curCur ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" style="min-width:200px;">
                            <?php foreach (getCurrencyList() as $code => $ci): ?>
                                <li>
                                    <a class="dropdown-item <?= $code === $curCur ? 'active' : '' ?>"
                                        href="?set_currency=<?= $code ?>">
                                        <?= $ci['symbol'] ?>     <?= $code ?> — <?= $ci['name'] ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Ayarlar -->
                    <a href="<?= BASE_URL ?>/modules/settings/index.php" class="btn btn-sm btn-outline-secondary"
                        title="<?= __('settings') ?>" style="font-size:13px;">
                        <i class="bi bi-gear"></i>
                    </a>

                    <span class="text-muted small d-none d-lg-inline">
                        <i class="bi bi-clock me-1"></i><?= date('d.m.Y H:i') ?>
                    </span>

                    <div class="ms-2 border-start ps-3 d-flex align-items-center gap-3">
                        <div class="user-info text-end d-none d-md-block">
                            <div class="fw-bold small" style="line-height:1;">
                                <?= e($_SESSION['user_name'] ?? __('admin')) ?>
                            </div>
                            <div class="text-muted" style="font-size:10px;">@<?= e($_SESSION['username'] ?? 'admin') ?>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/public/logout.php" class="btn btn-sm btn-outline-danger"
                            title="<?= __('logout') ?>">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Flash mesaj -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?> alert-dismissible fade show mx-3 mt-3"
                    role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Sayfa içeriği buraya -->
            <div class="page-content">