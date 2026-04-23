<?php
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.'));
foreach ($it as $file) {
    if ($file->getExtension() === 'php') {
        $path = $file->getRealPath();
        if (strpos($path, 'vendor') !== false || strpos($path, 'var') !== false) continue;
        
        $error = exec("php -l \"$path\" 2>&1", $output, $return);
        if ($return !== 0) {
            echo "ERROR in $path:\n" . implode("\n", $output) . "\n";
        }
        $output = [];
    }
}
