<?php
/**
 * Çok Dilli Sistem (i18n)
 *
 * Cookie tabanlı dil seçimi. Varsayılan: en
 * Kullanım: __('product_name') → "Product Name" veya "Ürün Adı"
 */

// Dil cookie'den oku, yoksa 'en'
function getCurrentLang(): string
{
    return $_COOKIE['bah_lang'] ?? 'en';
}

function setLang(string $lang): void
{
    $allowed = ['tr', 'en', 'fr'];
    if (!in_array($lang, $allowed))
        $lang = 'en';
    setcookie('bah_lang', $lang, time() + 86400 * 365, '/');
    $_COOKIE['bah_lang'] = $lang;
}

// Dil tanımları
function getLangMeta(): array
{
    return [
        'tr' => ['name' => 'Türkçe', 'flag' => '🇹🇷', 'dir' => 'ltr'],
        'en' => ['name' => 'English', 'flag' => '🇬🇧', 'dir' => 'ltr'],
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷', 'dir' => 'ltr'],
    ];
}

// Çeviri fonksiyonu
function __(string $key, ...$args): string
{
    static $translations = null;
    if ($translations === null) {
        $translations = loadTranslations(getCurrentLang());
    }
    $text = $translations[$key] ?? $key;
    if (!empty($args)) {
        $text = vsprintf($text, $args);
    }
    return $text;
}

function loadTranslations(string $lang): array
{
    $translations = [];

    // 1. Her zaman en.php'yi temel olarak yükle (Fallback için)
    $enFile = dirname(__DIR__) . "/core/lang/en.php";
    if (file_exists($enFile)) {
        $translations = require $enFile;
    }

    // 2. İstelen dili üzerine yaz (Eğer en değilse)
    if ($lang !== 'en') {
        $langFile = dirname(__DIR__) . "/core/lang/{$lang}.php";
        if (file_exists($langFile)) {
            $langTranslations = require $langFile;
            $translations = array_merge($translations, $langTranslations);
        }
    }

    // 3. Veritabanından yükle (Custom / Override)
    try {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT string_key, string_value FROM translations WHERE lang_code = :l");
        $stmt->execute([':l' => $lang]);
        $dbTrans = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if ($dbTrans) {
            $translations = array_merge($translations, $dbTrans);
        }
    } catch (Exception $e) {
        // DB henüz hazır değilse veya hata varsa dosya ile devam et
    }

    return $translations;
}
