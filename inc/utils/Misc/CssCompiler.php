<?php

declare(strict_types=1);

namespace DevBroken\Utils\Misc;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 * @license MIT
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

final class CssCompiler
{
    // Expresiones regulares declaradas como constantes de clase limpias
    private const RE_TEMPLATES = '/[a-zA-Z0-9_\/\\\\:-]+/';
    private const RE_MEDIA = '/(@media[^{]+\{)(.*?)\}\s*(?=\s*@media|\s*$)/ms';
    private const RE_BLOCKS = '/([^{]+)\{(.*?)\}/ms';
    private const RE_CLASSES = '/\.([a-zA-Z0-9_\/\\\\:-]+)/';
    private const RE_COMMENTS = '!/\*[^*]*\*+([^/*][^*]*\*+)*/!';

    private string $media;
    private string $theme;

    public function __construct(string $media, string $theme)
    {
        $this->media = $media;
        $this->theme = $theme;
    }

    public function compiler(string $used, string $output): string
    {
        $use = TS_FRONTEND . "/assets/css/{$used}";
        $outputFile = TS_THEMES . '/' . TS_TEMA . "/{$output}";
        $views = TS_THEMES . '/' . TS_TEMA . '/templates';

        // 1. Recopilar contenido de vistas utilizando sintaxis funcional y compacta
        $contentsViews = '';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($views));
        foreach ($files as $archivo) {
            if ($archivo->isFile() && $archivo->getExtension() === 'tpl') {
                $contentsViews .= file_get_contents($archivo->getPathname());
            }
        }

        // Obtener un mapa optimizado de las clases usadas
        preg_match_all(self::RE_TEMPLATES, $contentsViews, $matches);
        $clases_usadas = array_flip($matches[0] ?? []);

        $css_original = file_get_contents($use);
        preg_match_all(self::RE_MEDIA, $css_original, $media_matches, PREG_SET_ORDER);

        // 2. Filtrar CSS global quitando las media queries de forma directa
        $css_global = preg_replace(self::RE_MEDIA, '', $css_original);
        $css_filtrado = $this->filtrar_bloques_css($css_global, $clases_usadas);

        // 3. Filtrar bloques dentro de Media Queries
        foreach ($media_matches as [, $header_media, $contenido_media]) {
            $interno_filtrado = $this->filtrar_bloques_css($contenido_media, $clases_usadas);

            if (trim($interno_filtrado) !== '') {
                $css_filtrado .= "{$header_media}{$interno_filtrado}}\n";
            }
        }

        // 4. Minificación optimizada encadenando regex y reemplazos directos
        $css_minificado = preg_replace(
            [self::RE_COMMENTS, '/\s*([\{\}:;,])\s*/', '/\s+/'],
            ['', '$1', ''],
            $css_filtrado
        );

        // Quitar saltos remanentes y espacios innecesarios finales
        $css_minificado = trim(str_replace(["\r", "\n", "\t"], '', $css_minificado));

        // Guardar resultado final
        $finalizado = "/** Generado por PHPost LTS | DevBroken **/\n{$css_minificado}";
        file_put_contents($outputFile, $finalizado);

        return "{$this->media}/{$this->theme}/{$output}";
    }

    private function filtrar_bloques_css(string $css_chunk, array $clases_usadas): string
    {
        $resultado = '';
        preg_match_all(self::RE_BLOCKS, $css_chunk, $bloques, PREG_SET_ORDER);

        foreach ($bloques as [, $selector, $propiedades]) {
            $selector_completo = trim($selector);
            preg_match_all(self::RE_CLASSES, $selector_completo, $match_clases);

            // PHP 8.4+ / 8.5: Usamos array_any para buscar de forma idónea y ultra rápida
            $guardar_este_bloque = array_any(
                $match_clases[1] ?? [],
                static function ($clase_css) use ($clases_usadas) {
                    $nombre_clase = str_replace(['\\:', '\\/', '\\'], [':', '/', ''], $clase_css);
                    return isset($clases_usadas[$nombre_clase]);
                }
            );

            if ($guardar_este_bloque) {
                $resultado .= "{$selector_completo}{{$propiedades}}\n";
            }
        }

        return $resultado;
    }
}
