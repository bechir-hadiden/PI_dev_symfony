<?php
$dir = new RecursiveDirectoryIterator('src');
$it = new RecursiveIteratorIterator($dir);
foreach ($it as $file) {
    if ($file->getExtension() === 'php') {
        $path = $file->getRealPath();
        exec("php -l \"$path\"", $output, $return);
        if ($return !== 0) {
            echo "SYNTAX ERROR in $path:\n";
            echo implode("\n", $output) . "\n";
        }
        $output = [];
    }
}
