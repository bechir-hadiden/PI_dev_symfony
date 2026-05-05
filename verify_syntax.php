<?php
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('src'));
foreach ($it as $file) {
    if ($file->getExtension() === 'php') {
        $path = $file->getRealPath();
        try {
            // We use proc_open to run php -l on each file to get clean output
            $resource = proc_open("php -l \"$path\"", [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ], $pipes);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $return = proc_close($resource);
            
            if ($return !== 0) {
                echo "ERROR in $path:\n$stdout\n$stderr\n";
            }
        } catch (Throwable $e) {
            echo "EXCEPTION in $path: " . $e->getMessage() . "\n";
        }
    }
}
