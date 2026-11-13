<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 * @license MIT
 */

/**
 * Smarty trim modifier plugin
 *
 * Tipo:     modifier
 * Nombre:   trim
 * Propósito: Limpia caracteres al inicio, al final o en ambos lados de una cadena.
 * Ejemplo:  {$string|trim:",":"right"}
 *
 * @param string $string Cadena de texto a limpiar.
 * @param string $characters Caracteres específicos a eliminar (por defecto espacios en blanco, saltos de línea, etc.).
 * @param string $type Dirección de la limpieza: 'both', 'left' o 'right'.
 * @return string
 */
function smarty_modifier_trim(string $string = '', string $characters = " \t\n\r\0\x0B", string $type = 'both'): string
{
    if ($string === '') {
        return '';
    }
    return match (strtolower($type)) {
        'left', 'ltrim'  => ltrim($string, $characters),
        'right', 'rtrim' => rtrim($string, $characters),
        default          => trim($string, $characters),
    };
}
