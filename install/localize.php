<?php
/**
 * Auto-Localize Script by Antigravity
 * 
 * Bu script, HTML tag'leri arasındaki ve placeholder="..." gibi sık kullanılan
 * Türkçe metinleri tespit eder, core/lang dosyalarına ekler ve 
 * PHP dosyalarındaki metinleri <?= __('key') ?> formatına çevirir.
 */

$baseDir = dirname(__DIR__);
$dirsToScan = [
    $baseDir . '/modules',
    $baseDir . '/public'
];

$langTr = include $baseDir . '/core/lang/tr.php';
$langEn = include $baseDir . '/core/lang/en.php';
$langFr = include $baseDir . '/core/lang/fr.php';

// Generate safe key
function makeKey($str)
{
    if (trim($str) === '')
        return '';
    $k = mb_strtolower(trim($str), 'UTF-8');
    // replace turkish chars
    $k = str_replace(
        ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü'],
        ['c', 'g', 'i', 'o', 's', 'u'],
        $k
    );
    // keep only alpha numeric
    $k = preg_replace('/[^a-z0-9\s_]/', '', $k);
    $k = preg_replace('/\s+/', '_', $k);
    $k = substr($k, 0, 30);
    $k = rtrim($k, '_');
    if ($k === '')
        return 'txt_' . substr(md5($str), 0, 6);
    return 'txt_' . $k; // prefix
}

$filesProcessed = [];
$newKeys = [];

function processDir($dir)
{
    global $filesProcessed;
    $files = glob($dir . '/*');
    foreach ($files as $file) {
        if (is_dir($file)) {
            processDir($file);
        } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $filesProcessed[] = $file;
        }
    }
}

foreach ($dirsToScan as $d) {
    processDir($d);
}

// Ignore files that are pure PHP logic or configs
$ignore = ['bootstrap.php', 'Database.php', 'helpers.php', 'lang.php'];

foreach ($filesProcessed as $file) {
    if (in_array(basename($file), $ignore))
        continue;

    $content = file_get_contents($file);
    $originalContent = $content;

    // 1. Text inside HTML tags >TEXT<
    $content = preg_replace_callback('/>([^<]*?[a-zA-ZçğıöşüÇĞİÖŞÜ][^<]*?)</', function ($m) use (&$newKeys) {
        $text = $m[1];
        // skip if has PHP tags
        if (strpos($text, '<?') !== false)
            return $m[0];
        // skip mostly symbols
        if (preg_match('/^[0-9\s.,!?:;%&()\[\]\-\/\\\'"]+$/', $text))
            return $m[0];

        $key = makeKey($text);
        if (!isset($newKeys[$key])) {
            $newKeys[$key] = trim($text);
        }
        return "><?= __('" . $key . "') ?><";
    }, $content);

    // 2. Text in placeholders placeholder="TEXT"
    $content = preg_replace_callback('/placeholder="([^"]*?[a-zA-ZçğıöşüÇĞİÖŞÜ][^"]*?)"/', function ($m) use (&$newKeys) {
        $text = $m[1];
        if (strpos($text, '<?') !== false)
            return $m[0];
        if (preg_match('/^[0-9\s.,!?:;%&()\[\]\-\/\\\'"]+$/', $text))
            return $m[0];

        $key = makeKey($text);
        if (!isset($newKeys[$key])) {
            $newKeys[$key] = trim($text);
        }
        return 'placeholder="<?= __(\'' . $key . '\') ?>"';
    }, $content);

    // 3. title="TEXT"
    $content = preg_replace_callback('/title="([^"]*?[a-zA-ZçğıöşüÇĞİÖŞÜ][^"]*?)"/', function ($m) use (&$newKeys) {
        $text = $m[1];
        if (strpos($text, '<?') !== false)
            return $m[0];
        if (preg_match('/^[0-9\s.,!?:;%&()\[\]\-\/\\\'"]+$/', $text))
            return $m[0];

        $key = makeKey($text);
        if (!isset($newKeys[$key])) {
            $newKeys[$key] = trim($text);
        }
        return 'title="<?= __(\'' . $key . '\') ?>"';
    }, $content);

    if ($originalContent !== $content) {
        file_put_contents($file, $content);
    }
}

// 4. Update language arrays
$pythonScript = <<<PY
import sys
import json
import os
try:
    from googletrans import Translator
except ImportError:
    print("WARNING: googletrans module not found. Installing...")
    import subprocess
    subprocess.check_call([sys.executable, "-m", "pip", "install", "googletrans==4.0.0-rc1"])
    from googletrans import Translator

in_file = sys.argv[1]
with open(in_file, 'r', encoding='utf-8') as f:
    keys = json.loads(f.read())

t = Translator()

en_dict = {}
fr_dict = {}
for k, v in keys.items():
    if v.strip() == '':
        continue
    try:
        en_dict[k] = t.translate(v, src='tr', dest='en').text
        fr_dict[k] = t.translate(v, src='tr', dest='fr').text
    except Exception as e:
        en_dict[k] = v
        fr_dict[k] = v

with open(in_file + '_out.json', 'w', encoding='utf-8') as f:
    f.write(json.dumps({'en': en_dict, 'fr': fr_dict}))
print("DONE")
PY;

file_put_contents($baseDir . '/install/trans.py', $pythonScript);
file_put_contents($baseDir . '/install/trans_in.json', json_encode($newKeys, JSON_UNESCAPED_UNICODE));

$cmd = escapeshellcmd("python " . $baseDir . "/install/trans.py") . " " . escapeshellarg($baseDir . '/install/trans_in.json');
$output = shell_exec($cmd);

$parsedTranslations = [];
if (file_exists($baseDir . '/install/trans_in.json_out.json')) {
    $parsedTranslations = json_decode(file_get_contents($baseDir . '/install/trans_in.json_out.json'), true);
}
$newEn = $parsedTranslations['en'] ?? [];
$newFr = $parsedTranslations['fr'] ?? [];

foreach ($newKeys as $k => $v) {
    if (!isset($langTr[$k]))
        $langTr[$k] = $v;
    if (!isset($langEn[$k]))
        $langEn[$k] = $newEn[$k] ?? $v;
    if (!isset($langFr[$k]))
        $langFr[$k] = $newFr[$k] ?? $v;
}

function writeLangFile($filePath, $array)
{
    $str = "<?php\n\nreturn [\n";
    foreach ($array as $k => $v) {
        $cleanV = str_replace("'", "\'", $v);
        $str .= "    '{$k}' => '{$cleanV}',\n";
    }
    $str .= "];\n";
    file_put_contents($filePath, $str);
}

writeLangFile($baseDir . '/core/lang/tr.php', $langTr);
writeLangFile($baseDir . '/core/lang/en.php', $langEn);
writeLangFile($baseDir . '/core/lang/fr.php', $langFr);

echo "SUCCESS! " . count($newKeys) . " keys extracted and translated.";
