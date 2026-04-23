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

foreach (['en', 'fr'] as $lang) {
    $langFile = $baseDir . '/core/lang/' . $lang . '.php';
    if (!file_exists($langFile))
        continue;
    $langArr = include $langFile;

    $newAdded = 0;
    foreach ($allKeys as $fullKey => $baseStr) {
        if (!isset($langArr[$fullKey])) {
            $readable = ucwords(str_replace('_', ' ', $baseStr));
            $langArr[$fullKey] = $readable;
            $newAdded++;
        }
    }

    $str = "<?php\n\nreturn [\n";
    foreach ($langArr as $k => $v) {
        $cleanV = str_replace("'", "\'", $v);
        $str .= "    '{$k}' => '{$cleanV}',\n";
    }
    $str .= "];\n";

    file_put_contents($langFile, $str);
    echo "Added $newAdded missing translations to $lang.php\n";
}
