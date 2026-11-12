<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

spl_autoload_register(function (string $class) {
    $prefix = 'DevBroken\\';
    $baseDir = __DIR__ . '/inc/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $parts = explode('\\', $relativeClass);
    if (isset($parts[0])) {
        $parts[0] = strtolower($parts[0]);
    }

    $file = $baseDir . implode('/', $parts) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
