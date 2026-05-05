<?php
try {
    putenv('APP_ENV=dev');
    putenv('APP_DEBUG=1');
    require_once 'vendor/autoload_runtime.php';
    return function (array $context) {
        return new Symfony\Bundle\FrameworkBundle\Console\Application(new App\Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']));
    };
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . "\n";
    echo "LINE: " . $e->getLine() . "\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
}
