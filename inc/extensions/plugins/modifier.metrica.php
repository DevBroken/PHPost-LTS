<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 * @license MIT
 */

/**
 * Smarty formato_metrico modifier plugin
 *
 * Tipo:     modifier
 * Nombre:   formato_metrico
 * Propósito: Convierte números grandes a formatos legibles (1.000 -> 1K, 1.500.000 -> 1.5M).
 * Ejemplo:  {$number|formato_metrico:1}
 *
 * @param mixed $number El número a formatear.
 * @param int $decimal Cantidad de decimales a mostrar tras la abreviación.
 * @return string
 */
function smarty_modifier_metrica(mixed $number, int $decimal = 0): string
{
	// Asegurar que el valor de entrada sea puramente numérico
	if (!is_numeric($number)) {
		return '0';
	}
	$valor = (float) $number;
	// Soporta hasta Terabytes/Petabytes si fuera necesario
	$sufijos = ['', 'K', 'M', 'G', 'T', 'P'];

	// Evitar bucles calculando el índice del sufijo matemáticamente
	$indice = $valor > 0 ? (int) intdiv((int) log10($valor), 3) : 0;

	// Limitar al máximo sufijo disponible en nuestro arreglo
	$indice = min($indice, count($sufijos) - 1);
	if ($indice === 0) {
		return (string) $number;
	}
	$valorReducido = $valor / (1000 ** $indice);
	// Formatear el número final con los decimales correspondientes
	return round($valorReducido, $decimal) . $sufijos[$indice];
}
