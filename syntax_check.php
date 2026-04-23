<?php
function check_syntax($dir)
{
    if (!is_dir($dir))
        return;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..')
            continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            check_syntax($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $output = [];
            $returnVar = 0;
            exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($path) . ' 2>&1', $output, $returnVar);
            if ($returnVar !== 0) {
                echo $path . "\n";
            }
        }
    }
}
check_syntax('c:\\xampp\\htdocs\\BAH_pharmacy');
echo "Script Done.\n";
