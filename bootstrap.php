<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

if (!defined('TS_HEADER')) {
    http_response_code(403);
    exit('Acceso denegado: No se permite la ejecución directa de este script.');
}

// Detección automática de entorno local (Localhost / Laragon / XAMPP)
$localhosts = ['localhost', '127.0.0.1', '::1'];
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', $localhosts, true)
    || str_ends_with($_SERVER['HTTP_HOST'] ?? '', '.local')
    || str_ends_with($_SERVER['HTTP_HOST'] ?? '', '.test');

// Detectar si la conexión cuenta con SSL activo actualmente
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 0) === 443;

// Inicialización inteligente de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => $isSecure,
        'use_only_cookies'=> true,
        'cookie_samesite' => 'Lax'
    ]);
}

// Configuración dinámica basada en el entorno detectado
$config = [
    'display' => [
        'ninguno'           => 0,
        'visitantes'        => 1,
        'desarrolladores'   => 2
    ],
    'mostrar'       => 'desarrolladores',
    'production'    => 0,
    'development'   => E_ALL
];

define('APP_STATUS', $isLocal);
define('APP_MODE', APP_STATUS ? 'development' : 'production');
define('APP_MODE_NUMBER', (APP_STATUS && $isLocal) ? '1' : '0');

// Aplicamos la configuración del reporte de errores
if (APP_STATUS) {
    error_reporting($config[APP_MODE]);
    ini_set('display_errors', APP_MODE_NUMBER);
}

// Límite de ejecución
set_time_limit(300);

/*
 * -------------------------------------------------------------------
 *  Definiendo constantes
 * -------------------------------------------------------------------
 */
define('TS_ROOT',       __DIR__);
define('TS_STORAGE',    TS_ROOT . '/storage');
define('TS_SOURCES',    TS_ROOT . '/inc');
define('TS_FRONTEND',   TS_ROOT . '/frontend');
define('TS_CLASS',      TS_SOURCES . '/class');
define('TS_EXTRA',      TS_SOURCES . '/extras');
define('TS_UTILS',      TS_SOURCES . '/utils');
define('TS_LIBS',       TS_SOURCES . '/libs');
// Frontend
define('TS_THEMES',     TS_FRONTEND . '/themes');
define('TS_AJAX',       TS_FRONTEND . '/ajax');
set_include_path(get_include_path() . PATH_SEPARATOR . realpath('./'));