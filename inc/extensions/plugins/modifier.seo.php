<?php

declare(strict_types=1);

/**
 * @author Miguel92 & PHPost Risus LTS
 * @copyright 2027
 */

/**
 * Smarty seo modifier plugin
 *
 * Tipo:     modifier
 * Nombre:   seo
 * Propósito: Genera slugs amigables (URLs limpias) optimizadas para SEO.
 * Ejemplo:  {$slug|seo:['max_length' => 50]}
 *
 * @param string $text Texto a transformar en slug.
 * @param array{lowercase?: bool, separator?: string, max_length?: ?int} $options Opciones de configuración.
 * @return string
 */
function smarty_modifier_seo(string $text, array $options = []): string
{
    // Aplicar valores por defecto de manera eficiente (PHP 7.4+)
    $options['lowercase'] ??= true;
    $options['separator'] ??= '-';
    $options['max_length'] ??= null;

    // Transliteración de alto rendimiento (á -> a, ñ -> n, Cyrillic -> Latin, etc.)
    $string = transliterator_transliterate('Any-Latin; Latin-ASCII;', $text);

    if ($string === false) {
        return '';
    }

    if ($options['lowercase']) {
        $string = mb_strtolower($string, 'UTF-8');
    }

    // Escapar el separador para la expresión regular
    $sep = preg_quote($options['separator'], '~');
    $regexCase = $options['lowercase'] ? '' : 'i';

    // Combinamos limpieza de caracteres especiales y unificación de espacios/separadores
    $string = preg_replace('~[^a-z0-9\s' . $sep . ']+~' . $regexCase, '', $string) ?? '';
    $string = preg_replace('~[\s' . $sep . ']+~', $options['separator'], $string) ?? '';
    $string = trim($string, $options['separator']);

    // Recorte inteligente de longitud máxima
    if ($options['max_length'] !== null && mb_strlen($string, 'UTF-8') > $options['max_length']) {
        $string = mb_substr($string, 0, $options['max_length'], 'UTF-8');
        $string = rtrim($string, $options['separator']);
    }

    return $string;
}
