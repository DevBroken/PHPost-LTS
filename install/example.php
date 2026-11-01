<?php

declare(strict_types=1);

/**
 * PHPost Risus LTS - Archivo de Configuración
 *
 * Este archivo sirve como plantilla base para la configuración del sistema.
 * El instalador automatizado leerá este archivo, reemplazará los marcadores
 * y generará el archivo 'config.inc.php' final en la raíz del proyecto.
 *
 * @author PHPost Risus LTS
 * @copyright 2027
 */

// Restricción de seguridad: Previene la ejecución directa desde el navegador
if (!defined('TS_HEADER')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso directo al script no permitido.');
}

return [

    /**
     *--------------------------------------------------------------------------
     * Credenciales de la Base de Datos
     *--------------------------------------------------------------------------
     * Parámetros esenciales para establecer la conexión con el servidor MySQL.
     */
    'hostname'  => '__hostname__', // Generalmente 'localhost' o una dirección IP
    'username'  => '__username__', // Nombre del usuario administrador de la base de datos
    'password'  => '__password__', // Contraseña asociada al usuario configurado
    'database'  => '__database__', // Nombre de la base de datos destinada a PHPost

    /**
     *--------------------------------------------------------------------------
     * Codificación y Reglas de Cotejo
     *--------------------------------------------------------------------------
     * Configuraciones por defecto para el soporte completo de caracteres modernos,
     * incluyendo soporte nativo para caracteres especiales y emojis (utf8mb4).
     */
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',

    /**
     *--------------------------------------------------------------------------
     * Parámetros Avanzados de Red
     *--------------------------------------------------------------------------
     * Puerto de comunicación del motor de base de datos.
     */
    'port'      => 3306 // Puerto por defecto para conexiones de MySQL/MariaDB
];
