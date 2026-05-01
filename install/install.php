<?php
/**
 * BAH Eczane Yönetim Sistemi — Kapsamlı Kurulum Sihirbazı
 *
 * Windows programı gibi next-next-finish mantığıyla çalışır.
 * Adımlar:
 *   1. Hoş Geldiniz & Lisans
 *   2. Sistem Gereksinimleri Kontrolü
 *   3. Veritabanı Bağlantısı
 *   4. Klasör & Tablo Oluşturma
 *   5. Kurulum Tamamlandı
 */

session_start();

// ── Zaten kuruluysa engelle ──────────────────────────
if (file_exists(dirname(__DIR__) . '/config/config.php')) {
    die('<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>BAH Eczane — Kurulum</title>
    <style>body{font-family:"Segoe UI",sans-serif;background:#0f2027;color:#eee;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
    .box{background:rgba(255,255,255,.06);padding:48px;border-radius:20px;text-align:center;max-width:480px;border:1px solid rgba(255,255,255,.1);}
    h2{color:#ef9a9a;margin:0 0 16px;} p{color:rgba(255,255,255,.6);line-height:1.7;font-size:14px;}
    code{background:rgba(0,0,0,.3);padding:2px 8px;border-radius:4px;font-size:13px;}
    a{display:inline-block;margin-top:24px;padding:12px 28px;background:#0ea5e9;color:#fff;text-decoration:none;border-radius:10px;font-weight:600;}</style></head><body>
    <div class="box"><h2>⚠️ Sistem Zaten Kurulu!</h2><p>Kurulum daha önce tamamlanmış.<br>Yeniden kurmak için <code>config/config.php</code> dosyasını silin.</p>
    <a href="../public/index.php">🚀 Sisteme Git</a>
    <a href="update.php" style="background:#f59e0b;margin-left:8px;">🔄 Update</a></div></body></html>');
}

// ── Mevcut adımı belirle ─────────────────────────────
$step = isset($_POST['step']) ? (int) $_POST['step'] : (isset($_GET['step']) ? (int) $_GET['step'] : 1);
$step = max(1, min(5, $step));

$result = ['success' => false, 'errors' => [], 'warnings' => []];
$basePath = dirname(__DIR__);

// ═══════════════════════════════════════════════════════
//  ADIM 2: SİSTEM GEREKSİNİMLERİ KONTROLÜ
// ═══════════════════════════════════════════════════════
function checkRequirements(): array
{
    $checks = [];

    // PHP Sürümü
    $checks[] = [
        'name' => 'PHP Sürümü',
        'required' => '7.4+',
        'current' => phpversion(),
        'ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'critical' => true,
    ];

    // PDO MySQL
    $checks[] = [
        'name' => 'PDO MySQL Eklentisi',
        'required' => 'Yüklü',
        'current' => extension_loaded('pdo_mysql') ? 'Yüklü' : 'Yüklü Değil',
        'ok' => extension_loaded('pdo_mysql'),
        'critical' => true,
    ];

    // mbstring
    $checks[] = [
        'name' => 'mbstring Eklentisi',
        'required' => 'Yüklü',
        'current' => extension_loaded('mbstring') ? 'Yüklü' : 'Yüklü Değil',
        'ok' => extension_loaded('mbstring'),
        'critical' => false,
    ];

    // GD (görsel işleme)
    $checks[] = [
        'name' => 'GD Kütüphanesi',
        'required' => 'Önerilir',
        'current' => extension_loaded('gd') ? 'Yüklü' : 'Yüklü Değil',
        'ok' => extension_loaded('gd'),
        'critical' => false,
    ];

    // JSON
    $checks[] = [
        'name' => 'JSON Eklentisi',
        'required' => 'Yüklü',
        'current' => extension_loaded('json') ? 'Yüklü' : 'Yüklü Değil',
        'ok' => extension_loaded('json'),
        'critical' => true,
    ];

    // session
    $checks[] = [
        'name' => 'Session Desteği',
        'required' => 'Aktif',
        'current' => function_exists('session_start') ? 'Aktif' : 'Pasif',
        'ok' => function_exists('session_start'),
        'critical' => true,
    ];

    // Upload boyutu
    $uploadMax = ini_get('upload_max_filesize');
    $uploadMB = convertToMB($uploadMax);
    $checks[] = [
        'name' => 'Maks Upload Boyutu',
        'required' => '≥ 2M',
        'current' => $uploadMax,
        'ok' => $uploadMB >= 2,
        'critical' => false,
    ];

    // Yazma izinleri
    $base = dirname(__DIR__);
    $dirs = ['/config', '/storage', '/storage/images', '/storage/invoices', '/storage/exports', '/storage/logs', '/core/lang', '/modules/settings'];
    foreach ($dirs as $dir) {
        $fullPath = $base . $dir;
        $writable = is_dir($fullPath) ? is_writable($fullPath) : is_writable(dirname($fullPath));
        $checks[] = [
            'name' => "Yazma İzni: {$dir}/",
            'required' => 'Yazılabilir',
            'current' => $writable ? 'Yazılabilir' : 'Yazılamaz',
            'ok' => $writable,
            'critical' => true,
        ];
    }

    return $checks;
}

function convertToMB(string $val): float
{
    $val = trim($val);
    $last = strtolower(substr($val, -1));
    $num = (float) $val;
    return match ($last) {
        'g' => $num * 1024,
        'm' => $num,
        'k' => $num / 1024,
        default => $num / 1048576,
    };
}

// ═══════════════════════════════════════════════════════
//  ADIM 3 POST: VERİTABANI TEST
// ═══════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 3) {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_port = trim($_POST['db_port'] ?? '3306');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';

    if (empty($db_host))
        $result['errors'][] = 'Sunucu adresi zorunlu.';
    if (empty($db_name))
        $result['errors'][] = 'Veritabanı adı zorunlu.';
    if (empty($db_user))
        $result['errors'][] = 'Kullanıcı adı zorunlu.';

    if (empty($result['errors'])) {
        try {
            $dsn = "mysql:host={$db_host};port={$db_port};charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $ver = $pdo->query("SELECT VERSION()")->fetchColumn();
            $result['success'] = true;
            $result['db_version'] = $ver;

            // Session'a kaydet
            $_SESSION['install'] = compact('db_host', 'db_port', 'db_name', 'db_user', 'db_pass');

        } catch (PDOException $e) {
            $result['errors'][] = 'Bağlantı hatası: ' . $e->getMessage();
        }
    }
}

// ═══════════════════════════════════════════════════════
//  ADIM 4 POST: KURULUMU GERÇEKLEŞTIR
// ═══════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 4) {
    $db = $_SESSION['install'] ?? null;
    if (!$db) {
        $result['errors'][] = 'Oturum süresi dolmuş, lütfen 3. adıma dönün.';
    } else {
        try {
            $dsn = "mysql:host={$db['db_host']};port={$db['db_port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $db['db_user'], $db['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // 1️⃣ Veritabanı oluştur
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$db['db_name']}`");
            $result['steps'][] = '✅ Veritabanı oluşturuldu: ' . $db['db_name'];

            // 2️⃣ Tabloları oluştur
            $tables = getTableSQL();
            foreach (explode(';', $tables) as $stmt) {
                $stmt = trim($stmt);
                if ($stmt !== '')
                    $pdo->exec($stmt);
            }
            $tableCount = $pdo->query("SHOW TABLES")->rowCount();
            $result['steps'][] = "✅ {$tableCount} tablo oluşturuldu";

            // 3️⃣ Örnek kategoriler
            $pdo->exec("INSERT IGNORE INTO `categories` (`id`,`name`,`description`) VALUES
                (1,'Ağrı Kesici','Analjezik ve antipiretik ilaçlar'),
                (2,'Antibiyotik','Bakteri enfeksiyonlarına karşı ilaçlar'),
                (3,'Vitamin & Takviye','Vitamin ve mineral takviyeleri'),
                (4,'Cilt Ürünleri','Topikal kremler ve losyonlar'),
                (5,'Göz & Kulak','Damla ve pomadlar'),
                (6,'Diğer','Sınıflandırılmamış ürünler')");
            $result['steps'][] = '✅ Örnek kategoriler eklendi';

            // 3.5️⃣ Varsayılan para birimleri
            $pdo->exec("INSERT IGNORE INTO `currencies` (`code`,`symbol`,`name`,`position`,`decimal_sep`,`thousand_sep`,`is_default`) VALUES
                ('USD', '$', 'US Dollar', 'before', '.', ',', 1),
                ('TRY', '₺', 'Turkish Lira', 'after', ',', '.', 0),
                ('EUR', '€', 'Euro', 'before', ',', '.', 0)
            ");
            $result['steps'][] = '✅ Varsayılan para birimleri eklendi';

            // 3.6️⃣ Varsayılan Admin
            $hash = password_hash('1234', PASSWORD_DEFAULT);
            $pdo->prepare("INSERT IGNORE INTO `users` (`username`, `password`, `first_name`, `last_name`) VALUES ('admin', :pw, 'Yönetici', 'Admin')")
                ->execute([':pw' => $hash]);
            $result['steps'][] = '✅ Varsayılan kullanıcı (admin/1234) oluşturuldu';

            // 4️⃣ Klasörler oluştur
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
                '/storage/exports'
            ];
            $created = 0;
            foreach ($dirs as $d) {
                $p = $basePath . $d;
                if (!is_dir($p)) {
                    mkdir($p, 0755, true);
                    $created++;
                }
            }
            $result['steps'][] = "✅ Klasör yapısı hazır ({$created} yeni klasör)";

            // 5️⃣ .htaccess güvenlik dosyaları
            $deny = "Order deny,allow\nDeny from all\n";
            file_put_contents($basePath . '/config/.htaccess', $deny);
            file_put_contents($basePath . '/storage/logs/.htaccess', $deny);
            file_put_contents($basePath . '/storage/exports/.htaccess', $deny);
            file_put_contents($basePath . '/storage/.htaccess', "Options -Indexes\n");
            $result['steps'][] = '✅ Güvenlik dosyaları (.htaccess) güncellendi';

            // 6️⃣ config.php oluştur
            writeConfigFile($db);
            $result['steps'][] = '✅ config/config.php oluşturuldu';

            $result['success'] = true;
            unset($_SESSION['install']);

        } catch (Exception $e) {
            $result['errors'][] = 'Kurulum hatası: ' . $e->getMessage();
        }
    }
}

// ── Tablo SQL'leri ───────────────────────────────────
function getTableSQL(): string
{
    return <<<SQL
    CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `description` TEXT,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `products` (
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
        `unit` VARCHAR(50) DEFAULT 'Kutu',
        `currency` VARCHAR(10) DEFAULT 'USD',
        `image` VARCHAR(255) DEFAULT NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `customers` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `sales` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `customer_id` INT UNSIGNED,
        `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `discount_type` ENUM('none','percent','fixed') NOT NULL DEFAULT 'none',
        `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `final_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `remaining_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `currency` VARCHAR(10) DEFAULT 'USD',
        `due_date` DATE DEFAULT NULL,
        `note` TEXT,
        `invoice_path` VARCHAR(255) DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `sale_items` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `sale_id` INT UNSIGNED NOT NULL,
        `product_id` INT UNSIGNED NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `total_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `payments` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `stock_movements` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT UNSIGNED NOT NULL,
        `type` ENUM('in','out','convert','adjust') NOT NULL,
        `quantity` INT NOT NULL,
        `reference` VARCHAR(100) DEFAULT NULL,
        `note` TEXT,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `logs` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `action` VARCHAR(255) NOT NULL,
        `user` VARCHAR(150) DEFAULT 'system',
        `ip` VARCHAR(45) DEFAULT NULL,
        `detail` TEXT,
        `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `currencies` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(10) NOT NULL UNIQUE,
        `symbol` VARCHAR(10) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `position` ENUM('before','after') DEFAULT 'before',
        `decimal_sep` VARCHAR(5) DEFAULT '.',
        `thousand_sep` VARCHAR(5) DEFAULT ',',
        `current_rate` DECIMAL(16,6) DEFAULT NULL,
        `rate_date` DATE DEFAULT NULL,
        `is_default` TINYINT(1) DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `exchange_rates` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `currency_code` VARCHAR(10) NOT NULL,
        `rate_to_usd` DECIMAL(16,6) NOT NULL,
        `effective_date` DATE NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_code_date` (`currency_code`, `effective_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `users` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `first_name` VARCHAR(100),
        `last_name` VARCHAR(100),
        `session_timeout` INT UNSIGNED DEFAULT 30,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `settings` (
        `key` VARCHAR(100) PRIMARY KEY,
        `value` TEXT,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL;
}

// ── Config dosyası oluştur ───────────────────────────
function writeConfigFile(array $db): void
{
    $base = dirname(__DIR__);
    $now = date('Y-m-d H:i:s');
    $enc = base64_encode($db['db_pass']);
    $content = "<?php\n// BAH Eczane — Yapılandırma | Oluşturma: {$now}\ndate_default_timezone_set('Europe/Istanbul');\n";
    $content .= "define('APP_NAME','BAH Pharmacy');\ndefine('APP_VERSION','1.1.0');\ndefine('APP_ENV','development');\n";
    $content .= "define('DB_HOST','{$db['db_host']}');\ndefine('DB_PORT','{$db['db_port']}');\n";
    $content .= "define('DB_NAME','{$db['db_name']}');\ndefine('DB_USER','{$db['db_user']}');\n";
    $content .= "define('DB_PASS',base64_decode('{$enc}'));\ndefine('DB_CHARSET','utf8mb4');\n";
    $content .= "define('BASE_PATH',dirname(__DIR__));\ndefine('PUBLIC_PATH',BASE_PATH.'/public');\n";
    $content .= "define('STORAGE_PATH',BASE_PATH.'/storage');\ndefine('MODULE_PATH',BASE_PATH.'/modules');\n";
    $content .= "\$protocol=(!empty(\$_SERVER['HTTPS'])&&\$_SERVER['HTTPS']!=='off')?'https':'http';\n";
    $content .= "\$host_name=\$_SERVER['HTTP_HOST']??'localhost';\n";
    $content .= "\$script_dir = str_replace('\\\\', '/', dirname(\$_SERVER['SCRIPT_NAME']));\n";
    $content .= "define('BASE_URL', \$protocol.'://'.\$host_name . ((\$script_dir == '/' || \$script_dir == '\\\\') ? '' : rtrim(\$script_dir, '/')));\n";
    $content .= "define('CSRF_TOKEN_LENGTH',32);\n";
    $content .= "define('INVOICE_DIR',STORAGE_PATH.'/invoices');\ndefine('EXPORT_DIR',STORAGE_PATH.'/exports');\n";
    $content .= "define('IMAGE_DIR',STORAGE_PATH.'/images');\ndefine('LOG_DIR',STORAGE_PATH.'/logs');\n";
    $content .= "if(APP_ENV==='development'){ini_set('display_errors',1);error_reporting(E_ALL);}else{ini_set('display_errors',0);error_reporting(0);}\n";
    file_put_contents($base . '/config/config.php', $content);
}

// ── Gereksinim kontrol verileri ──────────────────────
$requirements = ($step === 2) ? checkRequirements() : [];
$hasCriticalFail = false;
foreach ($requirements as $r) {
    if ($r['critical'] && !$r['ok']) {
        $hasCriticalFail = true;
        break;
    }
}

// ── UI: Arayüz başlat ───────────────────────────────
require_once __DIR__ . '/install_ui.php';
