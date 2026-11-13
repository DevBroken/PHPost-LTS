<?php

declare(strict_types=1);

namespace DevBroken\Utils\Misc;

use DevBroken\Utils\Misc\CssCompiler;

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 * @license MIT
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

require_once TS_UTILS . '/Misc/CssCompiler.php';

use tsCore;

final class Sources {

    protected tsCore $tsCore;
    protected CssCompiler $Compiler;

    private string $media;
    private string $theme;

    // Mapeo directo de extensiones a sus respectivas subcarpetas
    private array $extensionMap = [
        'css'   => 'css',
        'js'    => 'js',
        'png'   => 'images',
        'gif'   => 'images',
        'jpg'   => 'images',
        'jpeg'  => 'images',
        'webp'  => 'images',
        'svg'   => 'images',
        'ico'   => 'images',
        'woff'  => 'fonts',
        'woff2' => 'fonts',
        'ttf'   => 'fonts',
        'eot'   => 'fonts'
    ];

    public string $domain;

    public function __construct(tsCore $tsCore)
    {
        $this->tsCore = $tsCore;
        $this->media = rtrim($tsCore->settings['url'], '/');
        $this->theme = $tsCore->getTema('t_path') ?? 'default';
        $this->domain = $tsCore->getDomain();
        $this->Compiler = new CssCompiler($this->media, $this->theme);
    }

    /**
     * Resuelve dinámicamente la subcarpeta basándose en la extensión del archivo
     */
    private function getFolderByExtension(string $file): string
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return $this->extensionMap[$extension] ?? '';
    }

    /**
     * Verifica la existencia física del archivo en el servidor
     */
    private function checkExistsFile(string $subfolder, string $file, string $type): bool
    {
        $root = ($type === 'theme') ? TS_THEMES . '/' . $this->theme : TS_FRONTEND . '/assets';
        $path = empty($subfolder) ? $file : $subfolder . '/' . $file;
        return file_exists($root . '/' . $path);
    }

    /**
     * Procesa y genera la URL final comprobando múltiples rutas posibles
     */
    private function getFileUse(string $baseFolder, string $type, string $file): string
    {
        // Obtener la subcarpeta sugerida por la extensión (ej: 'css')
        $subfolder = $this->getFolderByExtension($file);
        if ($this->checkExistsFile('', $file, $type)) {
            return $this->media . '/' . $baseFolder . '/' . $file;
        }
        if (!empty($subfolder) && $this->checkExistsFile($subfolder, $file, $type)) {
            return $this->media . '/' . $baseFolder . '/' . $subfolder . '/' . $file;
        }
        return '';
    }

    /**
     * @access public
     * @param string $file Nombre del archivo con su extensión (ej: 'live.css')
     * @return string
     */
    public function assets(string $file): string
    {
        return $this->getFileUse('assets', 'assets', $file);
    }

    /**
     * @access public
     * @param string $file Nombre del archivo con su extensión (ej: 'styles.css')
     * @return string
     */
    public function theme(string $file): string
    {
        return $this->getFileUse($this->theme, 'theme', $file);
    }

    public function svg(string $file): string {
        $file .= '.svg';
        $exists =  TS_FRONTEND . '/assets/images/icons/' . $file;
        if(file_exists($exists)) {
            $data = file_get_contents($exists);
            return $data;
        }
        return '';
    }

    /**
     * Genera rutas directas hacia directorios específicos (URLs web)
     *
     * @param string $type 'assets' o 'theme'
     * @param string $folder Nombre de la carpeta (ej: 'images', 'css')
     * @return string
     */
    public function route(string $type, string $folder = ''): string
    {
        $base = ($type === 'theme') ? $this->theme : 'assets';
        $path = $this->media . '/' . $base;

        if (!empty($folder)) {
            $path .= '/' . trim($folder, '/');
        }

        return $path;
    }

    public function compiler(string $used, string $output, bool $debug = false) {
        if($debug) {
            return $this->Compiler->compiler($used, $output);
        }
        return $this->theme($output);
    }

}
