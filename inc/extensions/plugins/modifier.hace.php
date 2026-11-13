<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 * @license MIT
 */

/**
 * Smarty hace modifier plugin
 *
 * Tipo:     modifier
 * Nombre:   hace
 * Propósito: Muestra el tiempo transcurrido de forma legible y elegante.
 *
 * @param mixed $fecha Timestamp numérico o string asegurable.
 * @param bool $show Parámetro opcional heredado.
 * @return string
 */
function smarty_modifier_hace(mixed $fecha, ?bool $show = false): string
{
    $fechaInt = is_numeric($fecha) ? (int) $fecha : strtotime((string) $fecha);

    if (!$fechaInt) {
        return 'Fecha inválida';
    }

    $tiempo = time() - $fechaInt;

    if ($tiempo < 0) {
        return 'En el futuro';
    }

    if ($tiempo < 60) {
        return 'Hace unos segundos';
    }

    // Definición formal de intervalos temporales en segundos
    $intervalos = [
        31536000 => ['año', 'años', 'Más de un año', 'Más de %d años'],
        2592000  => ['mes', 'meses', 'Más de 1 mes', 'Más de %d meses'],
        86400    => ['día', 'días', 'Ayer', 'Hace %d días'],
        3600     => ['hora', 'horas', 'Hace 1 hora', 'Hace %d horas'],
        60       => ['minuto', 'minutos', 'Hace 1 minuto', 'Hace %d minutos'],
    ];

    foreach ($intervalos as $segundos => [$singular, $plural, $textoSingular, $textoPlural]) {
        $cantidad = intdiv($tiempo, $segundos);
        if ($cantidad === 0) {
            continue;
        }
        // Caso especial para días (Ayer) u otras etiquetas fijas singulares
        if ($cantidad === 1) {
            return $textoSingular;
        }
        return sprintf($textoPlural, $cantidad);
    }

    return 'Hace unos segundos';
}