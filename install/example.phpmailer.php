<?php

declare(strict_types=1);

/**
 * PHPost Risus LTS - Archivo de Configuración de Correo
 *
 * Este archivo sirve como plantilla base para la configuración de PHPMailer.
 * El instalador automatizado leerá este archivo, reemplazará los marcadores
 * y generará el archivo 'phpmailer.inc.php' final en la raíz del proyecto.
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
     * Depuración del Servidor SMTP
     *--------------------------------------------------------------------------
     * Controla el nivel de logs generados por PHPMailer para el diagnóstico.
     * 0 = Apagado (Producción) | 1 = Mensajes del cliente | 2 = Cliente y Servidor
     */
    'mail_debug'    => 0, // Por defecto 0 en plantillas para evitar fugas de información

    /**
     *--------------------------------------------------------------------------
     * Credenciales del Servidor de Correo
     *--------------------------------------------------------------------------
     * Parámetros esenciales para establecer la conexión con el servidor SMTP.
     */
    'mail_hostname' => '__mail_host__',     // Servidor SMTP (ej: smtp.mailtrap.io o ://gmail.com)
    'mail_username' => '__mail_username__', // Nombre de usuario o cuenta de correo de autenticación
    'mail_password' => '__mail_password__', // Contraseña asociada a la cuenta SMTP configurada
    'mail_port'     => 2525,                // Puerto de comunicación (ej: 25, 465, 587 o 2525)

    /**
     *--------------------------------------------------------------------------
     * Reglas de Autenticación y Seguridad
     *--------------------------------------------------------------------------
     * Protocolos de cifrado necesarios para proteger el envío de contraseñas.
     */
    'mail_auth'     => true,
    'mail_secure'   => '__mail_secure__',   // Protocolo de cifrado ('tls', 'ssl' o vacío)

    /**
     *--------------------------------------------------------------------------
     * Identidad del Remitente (Sender)
     *--------------------------------------------------------------------------
     * Define la dirección y el nombre que verán los usuarios en su bandeja de entrada.
     */
    'mail_from_email' => '__mail_from_email__', // Dirección de correo saliente (ej: no-reply@tusitio.com)
    'mail_from_name'  => '__mail_from_name__',  // Nombre visible del remitente (ej: PHPost Risus)
];
