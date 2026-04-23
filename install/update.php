<?php
/**
 * BAH Eczane — Güncelleme Sihirbazı (update.php)
 *
 * Mevcut kurulumda eksik olan bileşenleri otomatik ekler:
 *  - Eksik veritabanı tabloları / sütunları
 *  - Yeni klasörler (core/lang, modules/settings)
 *  - Dil dosyası kontrolü
 *  - .htaccess güvenlik dosyaları
 *  - Config sürüm güncelleme
 *
 * Tüm güncellemeler tek tıkla uygulanır.
 */

session_start();

$basePath = dirname(__DIR__);
$configFile = $basePath . '/config/config.php';

// Config yoksa install'a yönlendir
if (!file_exists($configFile)) {
    header('Location: install.php');
    exit;
}

require_once $configFile;
require_once $basePath . '/core/Database.php';

$pdo = Database::getInstance();
$logs = [];
$error = null;

// ═══════════════════════════════════════════════════════
//  POST: GÜNCELLEMELERİ UYGULA
// ═══════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ─── 1. Eksik Tablolar ─────────────────────────
        $existingTables = [];
        $tblResult = $pdo->query("SHOW TABLES");
        while ($row = $tblResult->fetch(PDO::FETCH_NUM)) {
            $existingTables[] = $row[0];
        }

        $requiredTables = [
            'categories' => "CREATE TABLE IF NOT EXISTS `categories` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(150) NOT NULL,
                `description` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'products' => "CREATE TABLE IF NOT EXISTS `products` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `barcode` VARCHAR(100) UNIQUE,
                `sku` VARCHAR(100),
                `name` VARCHAR(255) NOT NULL,
                `dosage_form` VARCHAR(100),
                `category_id` INT UNSIGNED,
                `purchase_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `sale_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `critical_stock` INT UNSIGNED NOT NULL DEFAULT 5,
                `stock_quantity` INT NOT NULL DEFAULT 0,
                `unit` VARCHAR(50) DEFAULT 'Box',
                `currency` VARCHAR(10) DEFAULT 'USD',
                `image` VARCHAR(255) DEFAULT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'customers' => "CREATE TABLE IF NOT EXISTS `customers` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `unique_id` VARCHAR(36) NOT NULL UNIQUE,
                `first_name` VARCHAR(100) NOT NULL,
                `last_name` VARCHAR(100) NOT NULL,
                `phone` VARCHAR(20),
                `address` TEXT,
                `payment_due_days` INT UNSIGNED DEFAULT 30,
                `total_debt` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `currency` VARCHAR(10) DEFAULT 'USD',
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'sales' => "CREATE TABLE IF NOT EXISTS `sales` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `customer_id` INT UNSIGNED,
                `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `discount_type` ENUM('none','percent','fixed') NOT NULL DEFAULT 'none',
                `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `final_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `remaining_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `currency` VARCHAR(10) DEFAULT 'USD',
                `note` TEXT,
                `invoice_path` VARCHAR(255) DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'sale_items' => "CREATE TABLE IF NOT EXISTS `sale_items` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `sale_id` INT UNSIGNED NOT NULL,
                `product_id` INT UNSIGNED NOT NULL,
                `quantity` INT NOT NULL DEFAULT 1,
                `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `total_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'payments' => "CREATE TABLE IF NOT EXISTS `payments` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `customer_id` INT UNSIGNED NOT NULL,
                `sale_id` INT UNSIGNED DEFAULT NULL,
                `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `method` ENUM('cash','card','transfer','other') NOT NULL DEFAULT 'cash',
                `currency` VARCHAR(10) DEFAULT 'USD',
                `note` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'stock_movements' => "CREATE TABLE IF NOT EXISTS `stock_movements` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `product_id` INT UNSIGNED NOT NULL,
                `type` ENUM('in','out','convert','adjust') NOT NULL,
                `quantity` INT NOT NULL,
                `reference` VARCHAR(100) DEFAULT NULL,
                `note` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'logs' => "CREATE TABLE IF NOT EXISTS `logs` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `action` VARCHAR(255) NOT NULL,
                `user` VARCHAR(150) DEFAULT 'system',
                `ip` VARCHAR(45) DEFAULT NULL,
                `detail` TEXT,
                `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'currencies' => "CREATE TABLE IF NOT EXISTS `currencies` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `code` VARCHAR(10) NOT NULL UNIQUE,
                `symbol` VARCHAR(10) NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `position` ENUM('before','after') DEFAULT 'before',
                `decimal_sep` VARCHAR(5) DEFAULT '.',
                `thousand_sep` VARCHAR(5) DEFAULT ',',
                `is_active` TINYINT(1) DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'exchange_rates' => "CREATE TABLE IF NOT EXISTS `exchange_rates` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `currency_code` VARCHAR(10) NOT NULL,
                `rate_to_usd` DECIMAL(16,6) NOT NULL,
                `effective_date` DATE NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_code_date` (`currency_code`, `effective_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'users' => "CREATE TABLE IF NOT EXISTS `users` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `first_name` VARCHAR(100),
                `last_name` VARCHAR(100),
                `session_timeout` INT UNSIGNED DEFAULT 30,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'settings' => "CREATE TABLE IF NOT EXISTS `settings` (
                `key` VARCHAR(100) PRIMARY KEY,
                `value` TEXT,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'translations' => "CREATE TABLE IF NOT EXISTS `translations` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `lang_code` VARCHAR(5) NOT NULL,
                `string_key` VARCHAR(255) NOT NULL,
                `string_value` TEXT,
                UNIQUE KEY `uq_lang_key` (`lang_code`, `string_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];

        foreach ($requiredTables as $tbl => $sql) {
            if (!in_array($tbl, $existingTables)) {
                $pdo->exec($sql);
                $logs[] = ['type' => 'success', 'msg' => "✅ Tablo oluşturuldu: <strong>$tbl</strong>"];

                // Eğer eklenen users ise varsayılan admini oluştur
                if ($tbl === 'users') {
                    $hash = password_hash('1234', PASSWORD_DEFAULT);
                    $pdo->prepare("INSERT IGNORE INTO `users` (`username`, `password`, `first_name`, `last_name`) VALUES ('admin', :pw, 'Yönetici', 'Admin')")
                        ->execute([':pw' => $hash]);
                    $logs[] = ['type' => 'success', 'msg' => "✅ Varsayılan kullanıcı (admin/1234) oluşturuldu."];
                }
            } else {
                $logs[] = ['type' => 'skip', 'msg' => "⏭ Table exists: <code>{$tbl}</code>"];
            }
        }

        // ─── 2. Eksik Sütunlar ─────────────────────────
        $columnUpdates = [
            ['sales', 'currency', "ALTER TABLE `sales` ADD COLUMN `currency` VARCHAR(10) DEFAULT 'USD' AFTER `remaining_amount`"],
            ['sales', 'due_date', "ALTER TABLE `sales` ADD COLUMN `due_date` DATE DEFAULT NULL AFTER `currency`"],
            ['payments', 'currency', "ALTER TABLE `payments` ADD COLUMN `currency` VARCHAR(10) DEFAULT 'USD' AFTER `method`"],
            ['products', 'currency', "ALTER TABLE `products` ADD COLUMN `currency` VARCHAR(10) DEFAULT 'USD' AFTER `unit`"],
            ['customers', 'currency', "ALTER TABLE `customers` ADD COLUMN `currency` VARCHAR(10) DEFAULT 'USD' AFTER `total_debt`"],
        ];

        foreach ($columnUpdates as [$table, $column, $sql]) {
            if (in_array($table, $existingTables)) {
                $colExists = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'")->rowCount();
                if (!$colExists) {
                    $pdo->exec($sql);
                    $logs[] = ['type' => 'success', 'msg' => "✅ Column added: <code>{$table}.{$column}</code>"];
                } else {
                    $logs[] = ['type' => 'skip', 'msg' => "⏭ Column exists: <code>{$table}.{$column}</code>"];
                }
            }
        }

        // ─── 3. Eksik Kategoriler ──────────────────────
        $catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        if ($catCount == 0) {
            $pdo->exec("INSERT IGNORE INTO `categories` (`id`,`name`,`description`) VALUES
                (1,'Painkillers','Analgesic and antipyretic drugs'),
                (2,'Antibiotics','Drugs against bacterial infections'),
                (3,'Vitamins & Supplements','Vitamin and mineral supplements'),
                (4,'Dermatology','Topical creams and lotions'),
                (5,'Eye & Ear','Drops and ointments'),
                (6,'Other','Uncategorized products')");
            $logs[] = ['type' => 'success', 'msg' => '✅ Default categories inserted'];
        }

        // ─── 3.5 Varsayılan Para Birimleri ─────────────
        $curCount = $pdo->query("SELECT COUNT(*) FROM currencies")->fetchColumn();
        if ($curCount == 0) {
            $pdo->exec("INSERT IGNORE INTO `currencies` (`code`,`symbol`,`name`,`position`,`decimal_sep`,`thousand_sep`) VALUES 
                ('USD', '$', 'US Dollar', 'before', '.', ','),
                ('TRY', '₺', 'Turkish Lira', 'after', ',', '.'),
                ('EUR', '€', 'Euro', 'before', ',', '.')
            ");
            $logs[] = ['type' => 'success', 'msg' => '✅ Default currencies inserted'];
        }

        $setCount = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
        if ($setCount == 0) {
            $defaultReports = json_encode([
                'show_logo' => true,
                'logo_size' => 60,
                'hide_header_nav' => true,
                'hide_sidebar' => true,
                'show_summary' => true
            ], JSON_UNESCAPED_UNICODE);
            $pdo->prepare("INSERT IGNORE INTO `settings` (`key`, `value`) VALUES ('report_settings', :v)")
                ->execute([':v' => $defaultReports]);
            $logs[] = ['type' => 'success', 'msg' => '✅ Default report settings inserted'];
        }

        // ─── 3.6 Translations Seeding ──────────────────
        $transCount = $pdo->query("SELECT COUNT(*) FROM translations")->fetchColumn();
        if ($transCount == 0) {
            $langs = ['tr', 'en', 'fr'];
            $addedCount = 0;
            foreach ($langs as $l) {
                $f = $basePath . "/core/lang/{$l}.php";
                if (file_exists($f)) {
                    $arr = require $f;
                    if (is_array($arr)) {
                        $stmtT = $pdo->prepare("INSERT IGNORE INTO translations (lang_code, string_key, string_value) VALUES (:l, :k, :v)");
                        foreach ($arr as $k => $v) {
                            $stmtT->execute([':l' => $l, ':k' => $k, ':v' => $v]);
                            $addedCount++;
                        }
                    }
                }
            }
            if ($addedCount > 0) {
                $logs[] = ['type' => 'success', 'msg' => "✅ Seeded {$addedCount} translations from PHP files to database."];
            }
        }

        // ─── 4. Klasörler ──────────────────────────────
        $dirs = [
            '/config',
            '/core',
            '/core/lang',
            '/modules/products',
            '/modules/customers',
            '/modules/sales',
            '/modules/reports',
            '/modules/stock',
            '/modules/settings',
            '/public',
            '/public/assets/css',
            '/public/assets/js',
            '/public/assets/img',
            '/storage/invoices',
            '/storage/images',
            '/storage/logs',
            '/storage/exports',
        ];
        $createdDirs = 0;
        foreach ($dirs as $d) {
            $p = $basePath . $d;
            if (!is_dir($p)) {
                mkdir($p, 0755, true);
                $createdDirs++;
            }
        }
        $logs[] = [
            'type' => $createdDirs > 0 ? 'success' : 'skip',
            'msg' => $createdDirs > 0 ? "✅ {$createdDirs} new directories created" : '⏭ All directories exist'
        ];

        // ─── 5. Dil Dosyaları Kontrolü ─────────────────
        $langDir = $basePath . '/core/lang';
        $langFiles = ['en.php', 'tr.php', 'fr.php'];
        $missingLangs = [];
        foreach ($langFiles as $lf) {
            if (!file_exists($langDir . '/' . $lf)) {
                $missingLangs[] = $lf;
            }
        }
        if (!empty($missingLangs)) {
            $logs[] = ['type' => 'warning', 'msg' => '⚠ Missing language files: <code>' . implode(', ', $missingLangs) . '</code> — Please ensure they exist in <code>core/lang/</code>'];
        } else {
            $logs[] = ['type' => 'skip', 'msg' => '⏭ All language files present (en, tr, fr)'];
        }

        // ─── 6. Core Dosya Kontrolü ────────────────────
        $coreFiles = [
            '/core/lang.php' => 'Multi-language system',
            '/core/currency.php' => 'Currency system',
            '/core/Database.php' => 'Database class',
            '/core/helpers.php' => 'Helper functions',
            '/core/bootstrap.php' => 'Bootstrap loader',
            '/core/layout_header.php' => 'Layout header',
            '/core/layout_footer.php' => 'Layout footer',
        ];
        $missingCore = [];
        foreach ($coreFiles as $f => $desc) {
            if (!file_exists($basePath . $f)) {
                $missingCore[] = $f;
            }
        }
        if (!empty($missingCore)) {
            $logs[] = ['type' => 'warning', 'msg' => '⚠ Missing core files: <code>' . implode(', ', $missingCore) . '</code>'];
        } else {
            $logs[] = ['type' => 'skip', 'msg' => '⏭ All core files present'];
        }

        // ─── 7. .htaccess Güvenlik ─────────────────────
        $deny = "Order deny,allow\nDeny from all\n";
        $htFiles = ['/storage/logs/.htaccess', '/storage/exports/.htaccess', '/config/.htaccess', '/storage/backups/.htaccess'];
        foreach ($htFiles as $hf) {
            if (!file_exists($basePath . $hf)) {
                @file_put_contents($basePath . $hf, $deny);
                $logs[] = ['type' => 'success', 'msg' => "✅ Security file created: <code>{$hf}</code>"];
            }
        }
        $storageHt = '/storage/.htaccess';
        if (!file_exists($basePath . $storageHt)) {
            @file_put_contents($basePath . $storageHt, "Options -Indexes\n");
            $logs[] = ['type' => 'success', 'msg' => "✅ Open security file created: <code>{$storageHt}</code>"];
        } else {
            // Eğer eskiyse ve deny from all varsa onu düzelt
            $content = @file_get_contents($basePath . $storageHt);
            if (strpos($content, 'Deny from all') !== false) {
                @file_put_contents($basePath . $storageHt, "Options -Indexes\n");
            }
        }

        // ─── 8. Module Dosya Kontrolü ──────────────────
        $moduleFiles = [
            '/modules/settings/index.php' => 'Settings page',
            '/modules/settings/currencies.php' => 'Currency management',
            '/modules/stock/entry.php' => 'Stock Entry module',
            '/modules/stock/movements.php' => 'Stock Movements',
            '/modules/stock/convert.php' => 'Stock Conversion',
            '/modules/reports/index.php' => 'Reports dashboard',
            '/modules/reports/export.php' => 'CSV Export',
            '/modules/reports/logs.php' => 'Action logs',
            '/modules/reports/stock_report.php' => 'Stock report',
        ];
        $missingModules = [];
        foreach ($moduleFiles as $f => $desc) {
            if (!file_exists($basePath . $f)) {
                $missingModules[] = basename($f) . " ({$desc})";
            }
        }
        if (!empty($missingModules)) {
            $logs[] = ['type' => 'warning', 'msg' => '⚠ Missing module files: <code>' . implode(', ', $missingModules) . '</code>'];
        } else {
            $logs[] = ['type' => 'skip', 'msg' => '⏭ All module files present'];
        }

        // ─── 8.5. Müşteri ID Güncelleme (12 Haneli Yeni Standart) ──
        $custUpdateQuery = $pdo->query("SELECT id, first_name, unique_id FROM customers WHERE CHAR_LENGTH(unique_id) != 12 OR unique_id LIKE '%-%'");
        $custToUpdate = $custUpdateQuery->fetchAll();
        if (count($custToUpdate) > 0) {
            foreach ($custToUpdate as $c) {
                $prefix = mb_strtoupper(mb_substr(trim($c['first_name']), 0, 3, 'UTF-8'), 'UTF-8');
                $prefix = str_pad($prefix, 3, 'X');
                $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $random = '';
                for ($i = 0; $i < 9; $i++) {
                    $random .= $chars[random_int(0, strlen($chars) - 1)];
                }
                $newId = $prefix . $random;
                $pdo->prepare("UPDATE customers SET unique_id = :uid WHERE id = :id")->execute([':uid' => $newId, ':id' => $c['id']]);
            }
            $logs[] = ['type' => 'success', 'msg' => '✅ Migrated ' . count($custToUpdate) . ' customers to new 12-char ID format.'];
        }

        // ─── 9. Sürüm Güncelleme ──────────────────────
        $logs[] = ['type' => 'success', 'msg' => '✅ Update check completed — System version: <strong>v1.2.0</strong>'];

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAH Pharmacy — System Update</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f2027, #203a43, #0f2027);
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .container {
            max-width: 720px;
            width: 100%;
        }

        .card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(12px);
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        h1 i {
            font-size: 28px;
            margin-right: 8px;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
            margin-bottom: 32px;
        }

        .log-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 14px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 10px;
            margin-bottom: 6px;
            font-size: 13px;
            line-height: 1.6;
            border-left: 3px solid rgba(255, 255, 255, 0.08);
        }

        .log-item.success {
            border-left-color: #22c55e;
        }

        .log-item.skip {
            border-left-color: #64748b;
            color: rgba(255, 255, 255, 0.4);
        }

        .log-item.warning {
            border-left-color: #f59e0b;
            background: rgba(245, 158, 11, 0.04);
        }

        .log-item code {
            background: rgba(0, 0, 0, 0.3);
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 12px;
        }

        .btn {
            display: inline-block;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s;
        }

        .btn-primary {
            background: #0ea5e9;
            color: #fff;
        }

        .btn-primary:hover {
            background: #0284c7;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-warn {
            background: #f59e0b;
            color: #000;
        }

        .btn-warn:hover {
            background: #d97706;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }

        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            color: #fca5a5;
            font-size: 14px;
        }

        .info-box {
            background: rgba(14, 165, 233, 0.06);
            border: 1px solid rgba(14, 165, 233, 0.15);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 28px;
            font-size: 14px;
            line-height: 1.8;
        }

        .info-box strong {
            color: #0ea5e9;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            font-size: 13px;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .badge-ok {
            color: #22c55e;
        }

        .badge-warn {
            color: #f59e0b;
        }

        .badge-err {
            color: #ef4444;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="card">

            <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
                <!-- ═══ PRE-UPDATE EKRANI ═══ -->
                <h1>🔄 System Update</h1>
                <p class="subtitle">BAH Pharmacy — Update check and migration tool</p>

                <div class="info-box">
                    <strong>What does this update do?</strong><br>
                    ◆ Checks and creates missing database tables & columns<br>
                    ◆ Creates <code>currencies</code> and <code>exchange_rates</code> tables<br>
                    ◆ Adds new directories (<code>core/lang</code>, <code>modules/settings</code>)<br>
                    ◆ Verifies language files (EN, TR, FR)<br>
                    ◆ Checks all core and module files<br>
                    ◆ Creates missing security (.htaccess) files<br>
                    ◆ Adds multi-currency support columns to sales & payments<br>
                    ◆ Daily exchange rate system (USD base)
                </div>

                <!-- Pre-check summary -->
                <?php
                // Quick pre-check
                $preChecks = [];

                // DB tabloları
                $tblResult = $pdo->query("SHOW TABLES");
                $existingTables = [];
                while ($row = $tblResult->fetch(PDO::FETCH_NUM)) {
                    $existingTables[] = $row[0];
                }
                $preChecks[] = ['label' => 'Database Tables', 'value' => count($existingTables) . '/10', 'status' => count($existingTables) >= 10 ? 'ok' : 'warn'];

                // currencies tablosu
                $hasCurTbl = in_array('currencies', $existingTables) && in_array('exchange_rates', $existingTables);
                $preChecks[] = ['label' => 'Currency & Exchange Rate tables', 'value' => $hasCurTbl ? 'Present' : 'Missing', 'status' => $hasCurTbl ? 'ok' : 'warn'];

                // currencies.php modülü
                $curModOk = file_exists($basePath . '/modules/settings/currencies.php');
                $preChecks[] = ['label' => 'Currency management module', 'value' => $curModOk ? 'Installed' : 'Missing', 'status' => $curModOk ? 'ok' : 'warn'];

                // currency sütunu
                $hasCurCol = false;
                if (in_array('sales', $existingTables)) {
                    $hasCurCol = $pdo->query("SHOW COLUMNS FROM `sales` LIKE 'currency'")->rowCount() > 0;
                }
                $preChecks[] = ['label' => 'Multi-currency columns', 'value' => $hasCurCol ? 'Present' : 'Missing', 'status' => $hasCurCol ? 'ok' : 'warn'];

                // Dil dosyaları
                $langOk = file_exists($basePath . '/core/lang/en.php') && file_exists($basePath . '/core/lang/tr.php') && file_exists($basePath . '/core/lang/fr.php');
                $preChecks[] = ['label' => 'Language files', 'value' => $langOk ? 'All present' : 'Missing', 'status' => $langOk ? 'ok' : 'warn'];

                // Core dosyalar
                $coreOk = file_exists($basePath . '/core/lang.php') && file_exists($basePath . '/core/currency.php');
                $preChecks[] = ['label' => 'i18n / Currency core', 'value' => $coreOk ? 'Installed' : 'Missing', 'status' => $coreOk ? 'ok' : 'warn'];

                // Settings modülü
                $settingsOk = file_exists($basePath . '/modules/settings/index.php');
                $preChecks[] = ['label' => 'Settings module', 'value' => $settingsOk ? 'Installed' : 'Missing', 'status' => $settingsOk ? 'ok' : 'warn'];
                ?>

                <div style="margin-bottom:28px;">
                    <h3 style="font-size:15px;margin-bottom:12px;color:rgba(255,255,255,0.6);">Pre-Update Check</h3>
                    <?php foreach ($preChecks as $pc): ?>
                        <div class="summary-row">
                            <span>
                                <?= $pc['label'] ?>
                            </span>
                            <span class="badge-<?= $pc['status'] ?>">
                                <?= $pc['value'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST" action="update.php">
                    <div class="actions">
                        <button type="submit" class="btn btn-warn">🔄 Apply Updates</button>
                        <a href="../public/index.php" class="btn btn-secondary">← Back to System</a>
                    </div>
                </form>

            <?php else: ?>
                <!-- ═══ POST-UPDATE SONUÇ ═══ -->
                <h1>🔄 Update Results</h1>
                <p class="subtitle">All checks and migrations completed</p>

                <?php if ($error): ?>
                    <div class="error-box">❌
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom:24px;">
                    <?php
                    $successCount = count(array_filter($logs, fn($l) => $l['type'] === 'success'));
                    $warnCount = count(array_filter($logs, fn($l) => $l['type'] === 'warning'));
                    $skipCount = count(array_filter($logs, fn($l) => $l['type'] === 'skip'));
                    ?>
                    <div style="display:flex;gap:20px;margin-bottom:20px;font-size:14px;">
                        <span class="badge-ok">✅
                            <?= $successCount ?> applied
                        </span>
                        <span class="badge-warn">⚠
                            <?= $warnCount ?> warnings
                        </span>
                        <span style="color:rgba(255,255,255,0.4);">⏭
                            <?= $skipCount ?> skipped (already ok)
                        </span>
                    </div>
                </div>

                <div style="max-height:400px;overflow-y:auto;padding-right:8px;">
                    <?php foreach ($logs as $log): ?>
                        <div class="log-item <?= $log['type'] ?>">
                            <?= $log['msg'] ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="actions">
                    <a href="../public/index.php" class="btn btn-primary">🚀 Go to System</a>
                    <a href="update.php" class="btn btn-secondary">🔄 Re-check</a>
                </div>

            <?php endif; ?>

        </div>
    </div>

</body>

</html>