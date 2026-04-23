<?php
/**
 * Bootstrap (Önyükleyici)
 * 
 * Her sayfa bu dosyayı require_once eder.
 * Sırasıyla: config → core sınıflar → i18n → currency → session → güvenlik
 */

// Proje kök dizini (bootstrap.php /core/ içinde)
define('ROOT_PATH', dirname(__DIR__));

// Yapılandırma
$configFile = ROOT_PATH . '/config/config.php';
if (!file_exists($configFile)) {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $currentDir = str_replace('\\', '/', dirname($scriptName));
    // Eğer root'taysak direkt /install, değilse /folder/install
    $target = ($currentDir == '/' || $currentDir == '\\' ? '' : rtrim($currentDir, '/')) . '/install/install.php';
    header('Location: ' . $target);
    exit;
}
require_once $configFile;

// Core sınıflar
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/helpers.php';
require_once ROOT_PATH . '/core/lang.php';
require_once ROOT_PATH . '/core/currency.php';
require_once ROOT_PATH . '/core/theme.php';

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF token üret (yoksa)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
}

// GET ile dil değiştirme (?set_lang=tr)
if (isset($_GET['set_lang'])) {
    setLang($_GET['set_lang']);
    // Sayfayı yeniden yükle (set_lang parametresi olmadan)
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['set_lang']);
    $qs = http_build_query($params);
    header('Location: ' . $url . ($qs ? "?{$qs}" : ''));
    exit;
}

// GET ile para birimi değiştirme (?set_currency=USD)
if (isset($_GET['set_currency'])) {
    setCurrency($_GET['set_currency']);
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['set_currency']);
    $qs = http_build_query($params);
    header('Location: ' . $url . ($qs ? "?{$qs}" : ''));
    exit;
}

// --- AUTH CHECK ---
$currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$isLogin = str_contains($currentScript, 'login.php');
$isInstall = str_contains($currentScript, '/install/');
$isUpdate = str_contains($currentScript, '/update/');

if (!$isLogin && !$isInstall && !$isUpdate) {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/public/login.php');
        exit;
    }

    // Inactivity timeout check
    $userTimeout = ($_SESSION['user_timeout'] ?? 30) * 60;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $userTimeout)) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/public/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}
