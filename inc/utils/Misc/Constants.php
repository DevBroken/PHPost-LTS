<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

final class Constants {

    const COMENTARIO = [
        'oculto'    => 0,
        'activo'    => 1,
    ];

    const POST = [
        'oculto'    => 0,
        'activo'    => 1,
        'eliminado' => 2,
        'revision'  => 3
    ];

    const USUARIO = [
        'inactivo'  => 0,
        'activo'    => 1
    ];

    public static function useConst(string $type = 'post', string $status = 'activo'): int {
        return match($type) {
            'comentario' => (int)self::COMENTARIO[$status],
            'usuario' => (int)self::USUARIO[$status],
            default => (int)self::POST[$status]
        };
    }
}