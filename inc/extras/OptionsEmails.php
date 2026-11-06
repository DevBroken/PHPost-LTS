<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

defined('TS_HEADER') OR exit('No se permite el acceso directo al script');

$transactional = ['headers' => ['X-Notify-Type' => 'transactional']];
$security = ['headers' => ['X-Notify-Type' => 'security']];

return [
    'activar_cuenta' => [
        'subject' => 'Active su cuenta.', ...$transactional
    ],
    'bienvenida' => [
        'subject' => 'Bienvenido a nuestra comunidad.', ...$transactional
    ],
    'recuperar_contrasena' => [
        'subject' => 'Instrucciones para recuperar tu contraseña.', ...$transactional
    ],
    'cambiar_email' => [
        'subject' => 'Confirma el cambio de tu dirección de correo.', ...$security
    ],
    'alerta_seguridad' => [
        'subject' => 'Hemos detectado un intento de acceso a tu cuenta.',
        'headers' => ['X-Notify-Type' => 'alert', 'X-Priority' => '1']
    ],
    'soporte' => [
        'subject' => 'Tienes una respuesta de soporte.', ...$transactional
    ],
    'newsletter' => [
        'subject' => 'Últimas novedades y actualizaciones.',
        'headers' => ['X-Notify-Type' => 'newsletter']
    ],
    'aviso_importante' => [
        'subject' => 'Tienes un aviso importante del administrador.', ...$transactional
    ],
    'suspension' => [
        'subject' => 'Tu cuenta ha sido suspendida.',
        'headers' => ['X-Notify-Type' => 'alert', 'X-Priority' => '1']
    ],
    'actualizaciones' => [
        'subject' => 'Actualización importante del sistema.', ...$transactional
    ],
    'cuenta_eliminado' => [
        'subject' => 'Contenido eliminado', ...$transactional
    ],
    'desconocido' => [
        'subject' => 'Notificación',
        'headers' => []
    ]
];
