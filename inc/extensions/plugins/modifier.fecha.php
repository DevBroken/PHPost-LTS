<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

/**
 * Smarty fecha modifier plugin
 *
 * Tipo:     modifier
 * Nombre:   fecha
 * Propósito: Formatea fechas absolutas y relativas con un diseño limpio y moderno.
 *
 * @param mixed $fecha Timestamp numérico o string asegurable.
 * @param string|bool $format Formato específico o false para tiempo relativo.
 * @return string
 */
function smarty_modifier_fecha(mixed $fecha, string|bool $format = false): string
{
    $fechaInt = is_numeric($fecha) ? (int) $fecha : strtotime((string) $fecha);

    if (!$fechaInt) {
        return 'Fecha inválida';
    }

    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

    if ($format !== false) {
        $dia = date('d', $fechaInt);
        $mesInt = (int) date('n', $fechaInt);
        $ano = date('Y', $fechaInt);
        $hora = date('H:i', $fechaInt);

        return match ($format) {
            'd_Ms_a'     => (date('Y') === $ano) ? "{$dia} de {$meses[$mesInt]}" : "{$dia} de {$meses[$mesInt]} de {$ano}",
            'corto'      => date('d/m/Y H:i', $fechaInt),
            'extendido'  => sprintf('%s, %d de %s de %s', $dias[(int)date('w', $fechaInt)], $dia, $meses[$mesInt], $ano),
            'fecha_hora' => "{$dia} de {$meses[$mesInt]} de {$ano} a las {$hora}",
            'mes_ano'    => ucfirst($meses[$mesInt]) . " {$ano}",
            default      => date((string)$format, $fechaInt),
        };
    }

    $tiempo = time() - $fechaInt;
    if ($tiempo < 0) {
        return 'En el futuro';
    }
    if ($tiempo < 60) {
        return 'Hace unos segundos';
    }
    // Comprobaciones exactas de calendario para Hoy y Ayer
    $hoy = date('Y-m-d');
    $ayer = date('Y-m-d', strtotime('yesterday'));
    $fechaClave = date('Y-m-d', $fechaInt);
    $horaI = date('H:i', $fechaInt);
    if ($fechaClave === $hoy) {
        return $tiempo < 3600 ? sprintf('Hace %d %s', intdiv($tiempo, 60), intdiv($tiempo, 60) === 1 ? 'minuto' : 'minutos') : sprintf('Hace %d %s', intdiv($tiempo, 3600), intdiv($tiempo, 3600) === 1 ? 'hora' : 'horas');
    }

    if ($fechaClave === $ayer) {
        return "Ayer a las {$horaI}";
    }

    // De 2 a 7 días atrás
    $diasTranscurridos = intdiv($tiempo, 86400);
    if ($diasTranscurridos <= 7) {
        return sprintf('El %s a las %s', $dias[(int) date('w', $fechaInt)], $horaI);
    }

    // Más de una semana (Fecha absoluta reducida pero legible)
    return sprintf('El %d de %s a las %s', date('d', $fechaInt), $meses[$mesInt ?? (int) date('n', $fechaInt)], $horaI);
}
