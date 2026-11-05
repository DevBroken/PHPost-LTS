<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

// Chequeamos que este instalado antes de continuar
if(!file_exists(__DIR__ . '/config.inc.php')) {
    header("Location: ./install/index.php?step=welcome");
    exit();
}

// Incluimos header
require_once __DIR__ . '/header.php';

// Checamos...
if((int)$tsCore->allows['c_allow_portal'] && $tsUser->is_member && $_GET['do'] === 'portal') {
    // Portal/mi
    require_once __DIR__ . '/inc/php/portal.php';

} else {
    // Home
    require_once __DIR__ . '/inc/php/home.php';
}