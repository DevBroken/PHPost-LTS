<?php

/**
 * Autoloader personalizado para JBBCode, Smarty y PHPMailer
 */

$smarty_functions = __DIR__ . '/Smarty/functions.php';
if (file_exists($smarty_functions)) {
    require_once $smarty_functions;
}

spl_autoload_register(function ($class) {
    $base_dir = __DIR__ . '/';

    if (strpos($class, 'PHPMailer\\') === 0) {
        $relative_class = substr($class, 10);
        $file = $base_dir . 'PHPMailer/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    if (strpos($class, 'JBBCode\\') === 0) {
        $file = $base_dir . 'JBBCode/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    if (strpos($class, 'Smarty\\') === 0) {
        $relative_class = substr($class, 7);
        $file = $base_dir . 'Smarty/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
