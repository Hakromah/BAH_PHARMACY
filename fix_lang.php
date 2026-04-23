<?php
$baseDir = __DIR__;
$files = [];

function scan_dir($dir)
{
    global $files;
    $items = glob($dir . '/*');
    foreach ($items as $item) {
        if (is_dir($item)) {
            scan_dir($item);
        } elseif (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
            $files[] = $item;
        }
    }
}

scan_dir($baseDir . '/modules');
scan_dir($baseDir . '/public');
scan_dir($baseDir . '/core');

$allKeys = [];
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (preg_match_all("/__\('txt_([a-zA-Z0-9_]+)'\)/", $content, $matches)) {
        foreach ($matches[1] as $k) {
            $allKeys['txt_' . $k] = $k;
        }
    }
}

$langFile = $baseDir . '/core/lang/tr.php';
$langTr = include $langFile;

$newAdded = 0;
foreach ($allKeys as $fullKey => $baseStr) {
    if (!isset($langTr[$fullKey])) {
        // baseStr is like "uygulama_temasi_ui_theme"
        // Let's make it readable: "Uygulama Temasi Ui Theme"
        $readable = ucwords(str_replace('_', ' ', $baseStr));
        // Turkish corrections: 
        // e.g. "kategori adi" -> "Kategori Adı"
        $readable = str_replace(
            [' Kategori Adi', 'Kategori Adi', ' Temasi', ' Satis', ' Musteri', ' Urun', ' Ciro', ' Ayarlar', ' Araclar'],
            [' Kategori Adı', 'Kategori Adı', ' Teması', ' Satış', ' Müşteri', ' Ürün', ' Ciro', ' Ayarlar', ' Araçlar'],
            $readable
        );
        $langTr[$fullKey] = $readable;
        $newAdded++;
    }
}

// Generate the updated string
$str = "<?php\n\nreturn [\n";
foreach ($langTr as $k => $v) {
    $cleanV = str_replace("'", "\'", $v);
    $str .= "    '{$k}' => '{$cleanV}',\n";
}
$str .= "];\n";

file_put_contents($langFile, $str);
echo "Added $newAdded missing translations to tr.php\n";
