<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

defined('TS_HEADER') OR exit('No se permite el acceso directo al script');

require_once dirname(__DIR__, 1) . '/extensions/PHPostSmartyExtension.php';

class tsSmarty extends \Smarty\Smarty
{

	private string $theme;

	private string $page;

	public string $templateError = 't.error.tpl';

	public function __construct()
	{
		parent::__construct();

		// Habilita la comprobación de compilación para un rendimiento óptimo
		$compiled = APP_STATUS ? TRUE : \Smarty\Smarty::COMPILECHECK_OFF;
		$this->setCompileCheck($compiled);

		// Agrega directorio de plugins Smarty
		$this->plugins(TS_SOURCES . '/extensions/plugins/');

		// Suprime advertencias de variables indefinidas o nulas
		$this->muteUndefinedOrNullWarnings();

		// Auto-escape
		$this->setEscapeHtml(true);
	}

	private function plugins(string $path): void
	{
	    $path = rtrim($path, '/') . '/';
	    if (!is_dir($path)) return;
	    $plugins = scandir($path);
	    foreach ($plugins as $plugin) {
	        if (in_array($plugin, ['.', '..']) || substr_count($plugin, '.') < 2) {
	            continue;
	        }
	        // Ejemplo: "function.phpost_lts.php" -> type: "function", name: "phpost_lts"
	        [$pluginType, $pluginName] = explode('.', $plugin);
	        $file = $path . $plugin;
	        if (file_exists($file)) {
	            require_once $file;
	            $functionName = "smarty_{$pluginType}_{$pluginName}";
	            if (function_exists($functionName)) {
	                $this->registerPlugin($pluginType, $pluginName, $functionName);
	            }
	        }
	    }
	}

	public function setTheme(string $theme = ''): void {
		$this->theme = $theme ?: TS_TEMA;
		$this->setCompileDir(TS_STORAGE . '/cache/' . $theme);
	}

	public function setPage(string $page): void {
		$this->page = $page;
	}

	/**
	 * Modifica el comportamiento de salida de la plantilla, opcionalmente aplica filtro de eliminación de espacios en blanco.
	 *
	 * @param bool $loadFilter Determina si aplicar filtro de eliminación de espacios en blanco
	 */
	public function output(bool $loadFilter = false): void {
		if ($loadFilter) {
			$this->registerFilter('output', [new \Smarty\Filter\Output\TrimWhitespace(), 'filter']);
		}
	}

	private function recursiveDirectories(string $path): array {
		$iterator = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
		$iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
		$iterators = [];
		foreach ($iterator as $item) {
		   if ($item->isDir()) {
		      $iterators[$item->getFilename()] = $item->getPathname();
		   }
		}
		return $iterators;
	}

	/**
	 * Mapea rutas del sistema y módulos.
	 */
	private function mapDirectories(): array {
		// Para añadir mas directorios
		$directories = [
			'root' 		=> TS_ROOT,
			'frontend'	=> TS_FRONTEND,
			'auth'		=> TS_FRONTEND . '/auth'
		];
		return $directories;
	}

	/**
	 * Carga todos los directorios utilizados por el tema.
	 */
	private function loadAllTemplates(): void {
		$theme = TS_THEMES . '/' . $this->theme;
		$templates = "{$theme}/templates";
		$map = array_merge($this->mapDirectories(), [
				'tema'		=> $theme,
				'templates'	=> $templates
			],
			$this->recursiveDirectories($templates)
		);
		$this->addTemplateDir($map);
	}

	/**
	 * Renderiza una plantilla.
	 */
	public function load(bool $prefix = true): void {
		$this->loadAllTemplates();
		$page = ($prefix ? 't.' : '') . "{$this->page}.tpl";

		try {
			$template = $this->templateExists($page) ? $page : $this->templateError;

			$this->display($template);
		} catch (Exception $e) {
			$error_message = $e->getMessage();
			$pattern = '/template\s+"file:([^"]+)"\s+on\s+line\s+(\d+)/i';
			$ruta = 'Desconocido';
			$linea = 0;
			//
			if (preg_match($pattern, $error_message, $matches)) {
				$ruta = $matches[1];
    			$linea = $matches[2];
			}

			show_error(
				"Lo sentimos, se produjo un error al cargar la plantilla <strong>$page</strong>",
				'plantilla',
				['file' => $ruta, 'line' => $linea, 'query' => $error_message]
			);
		}
	}

	/**
	 * Borra la versión compilada del recurso de plantilla especificado.
	 *
	 * @param string $template Nombre de la plantilla compilada a borrar
	*/
	public function clearCompiled(string $template): void {
		$this->clearCompiledTemplate($template);
	}

}