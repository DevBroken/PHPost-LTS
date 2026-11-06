<?php

declare(strict_types=1);

/**
 * Smarty plugin: Modifier SEO
 * @author 		Miguel92
 * @copyright 	2027
 *
 * @param string $text
 * @param array $options
 * @return string
 */

function smarty_modifier_seo(string $text, array $options = []): string {
	// Configuración por defecto
	$defaults = [
		'lowercase' => true,	// true: todo minúsculas | false: mantiene mayúsculas
		'separator' => '-',		// Separador de palabras ('-' o '_')
		'max_length'=> null,	// Límite de caracteres (null para desactivar)
	];
	// Fusionar opciones del usuario con las por defecto
	$opts = array_merge($defaults, $options);
	$sep  = preg_quote($opts['separator'], '~');
	// Transliteración de alto rendimiento (á -> a, ñ -> n, Cyrillic -> Latin, etc.)
	$string = transliterator_transliterate('Any-Latin; Latin-ASCII;', $text);
	// Control de mayúsculas/minúsculas
	if ($opts['lowercase']) {
		$string = mb_strtolower($string, 'UTF-8');
	}
	// Limpiar caracteres no deseados (mantiene letras, números, espacios y el separador)
	// El flag 'i' de la regex se activa solo si no se forzó minúsculas
	$regex_case = $opts['lowercase'] ? '' : 'i';
	$string = preg_replace('~[^a-z0-9\s' . $sep . ']+~' . $regex_case, '', $string);
	// Unificar espacios y separadores repetidos en uno solo
	$string = preg_replace('~[\s' . $sep . ']+~', $opts['separator'], $string);
	$string = trim($string, $opts['separator']);
	// Recorte opcional por longitud máxima
	if ($opts['max_length'] !== null && mb_strlen($string, 'UTF-8') > $opts['max_length']) {
		$string = mb_substr($string, 0, $opts['max_length'], 'UTF-8');
		$string = rtrim($string, $opts['separator']);
	}
	return $string;
}