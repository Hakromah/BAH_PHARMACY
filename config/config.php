<?php
/**
 * BAH Eczane Yönetim Sistemi - Yapılandırma Dosyası
 * Oluşturulma: 2026-04-21 18:26:26
 *
 * DİKKAT: Bu dosyayı web üzerinden erişilebilir bir yere koymayın.
 * .htaccess ile erişim kapalıdır.
 */

// Uygulama ayarları
define('APP_NAME', 'BAH Pharmacy');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // production | development

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_NAME', 'bah_pharmacy');
define('DB_USER', 'root');
define('DB_PASS', base64_decode(''));
define('DB_CHARSET', 'utf8mb4');

// Yol sabitler
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('MODULE_PATH', BASE_PATH . '/modules');

// URL ayarları (sondaki slash olmadan)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host_name = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$current_dir = dirname($script_name);
$current_dir = ($current_dir === '\\' || $current_dir === '/') ? '' : str_replace('\\', '/', $current_dir);
// Eğer bir alt klasördeysek (örn: /public), o kısmı temizleyelim
$base_dir = preg_replace('/(\/public|\/install|\/modules.*)$/', '', $current_dir);
define('BASE_URL', $protocol . '://' . $host_name . rtrim($base_dir, '/'));

// Güvenlik
define('CSRF_TOKEN_LENGTH', 32);

// Fatura & rapor ayarları
define('INVOICE_DIR', STORAGE_PATH . '/invoices');
define('EXPORT_DIR', STORAGE_PATH . '/exports');
define('IMAGE_DIR', STORAGE_PATH . '/images');
define('LOG_DIR', STORAGE_PATH . '/logs');

// Hata raporlama
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
