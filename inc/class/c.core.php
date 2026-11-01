<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

defined('TS_HEADER') OR exit('No se permite el acceso directo al script');

require_once TS_SOURCES . '/extensions/plugins/modifier.seo.php';
require_once TS_EXTRA . '/bbcode.inc.php';

class tsCore {

	protected Logger $Logger;
	protected IP $IP;
	public array $settings = [];
	public array $register = [];
	public array $allows = [];

	public function __construct(Logger $Logger, IP $IP) {
		$this->Logger = $Logger;
		$this->IP = $IP;
		// La única configuraciones que importan
		$this->settings = $this->getSettings();
		$this->register = $this->setRegister();
		$this->allows 	= $this->setAllows();
	}

	/**
	 * @access public
	 * @param string $type
	 * @return string
	 */
	public function ip(string $type = 'execute'): string {
		$match = match($type) {
			'binary', 'ip_binary'	=> $this->IP->getIPBinary(),
			'human', 'ip_human' 	=> $this->IP->getIPHuman(),
			default					=> $this->IP->executeIP()
		};
		return $match;
	}

	/**
	 * @access public
	 * @param string $path
	 * @return string
	 */
	public function createRoute(string $path = ''): string {
		return $this->settings['url'] . $path;
	}

	/**
	 * @access public
	 * @param string $path
	 * @return string|array
	 */
	public function getRoutes(string $path = ''): string|array {
		$media = $this->settings['url'] . '/media';
		$tema 	= "{$media}/{$this->getTema('t_path')}";
		$assets = "{$media}/assets";
		// Creamos las rutas
		$routes = [];
		$routes['domain'] = $this->getDomain();
		foreach(['tema','assets'] as $directory) {
			foreach(['base','css','fonts','images','js'] as $subdirectory) {
				$routes[$directory][$subdirectory] = ($subdirectory === 'base') ? $$directory : "{$$directory}/{$subdirectory}";
			}
		}
		//
		if(!empty($path)) {
			$parts = explode(':', $path);
			if(count($parts) === 1) {
				return $routes[$parts[0]];
			} else if(count($parts) > 1) {
				return $routes[$parts[0]][$parts[1]];
			}
		}
		return $routes;
	}

	/**
	 * @access public
	 * @return array
	 */
	public function getSettings(): array {
		$query = db_exec([__FILE__, __LINE__], 'query', "SELECT titulo, slogan, url, email, tema, c_last_active, c_count_guests, c_fotos_private, c_hits_guest, c_keep_points, c_max_posts, c_max_com, c_max_nots, c_max_acts, c_newr_type, c_see_mod, c_stats_cache, c_desapprove_post, offline, offline_message, version, version_code FROM w_configuracion WHERE tscript_id = :id LIMIT 1", ['id' => 1]);
		return is_array($query) ? $query : [];
	}

	/**
	 * @access public
	 * @return array
	 */
	public function setAllows(): array {
		$allows = db_exec([__FILE__, __LINE__], 'query', "SELECT c_allow_sess_ip, c_allow_points, c_allow_sump, c_allow_firma, c_allow_upload, c_allow_portal, c_allow_live FROM w_configuracion WHERE tscript_id = :id LIMIT 1", ['id' => 1]);
		return is_array($allows) ? $allows : [];
	}

	/**
	 * @access public
	 * @return array
	 */
	public function setRegister(): array {
		$register = db_exec([__FILE__, __LINE__], 'query', "SELECT c_reg_active, c_reg_activate, c_reg_rango, c_met_welcome, c_message_welcome, c_allow_edad, pkey, skey FROM w_configuracion WHERE tscript_id = :id LIMIT 1", ['id' => 1]);
		return is_array($register) ? $register : [];
	}

	/**
	 * @access public
	 * @return array
	 */
	public function getNovemods(): array {
		$sql = "SELECT
			SUM(CASE WHEN p.post_status = 3 THEN 1 ELSE 0 END) as revposts,
			SUM(CASE WHEN p.post_status = 2 THEN 1 ELSE 0 END) as pospelera,
			(SELECT COUNT(cid) FROM p_comentarios WHERE c_status = 0) as revcomentarios,
			SUM(CASE WHEN d.d_type = 1 THEN 1 ELSE 0 END) as repposts,
			SUM(CASE WHEN d.d_type = 2 THEN 1 ELSE 0 END) as repmps,
			SUM(CASE WHEN d.d_type = 3 THEN 1 ELSE 0 END) as repusers,
			SUM(CASE WHEN d.d_type = 4 THEN 1 ELSE 0 END) as repfotos,
			(SELECT COUNT(susp_id) FROM u_suspension) as suspusers,
			(SELECT COUNT(foto_id) FROM f_fotos WHERE f_status = 2) as fospelera
			FROM (SELECT 1) __init
			LEFT JOIN p_posts p ON 1=1
			LEFT JOIN (SELECT DISTINCT obj_id, d_type FROM w_denuncias) d ON 1=1
			GROUP BY __init.1";

		$datos = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', $sql));
		// Asegurar que si la base de datos devuelve nulos o falla, no rompa la suma aritmética
		$datos = array_map('intval', $datos ?: []);
		$claves = array_flip([
			'repposts', 'repfotos', 'repmps', 'repusers', 'revposts', 'revcomentarios'
		]);
		// Filtramos el arreglo y sumamos de golpe de forma elegante
		$datos['total'] = array_sum(array_intersect_key($datos, $claves));
		//
		return $datos;
	}

	/**
	 * @access public
	 * @return array
	 */
	public function getCategorias(): array {
		return result_array(db_exec([__FILE__, __LINE__], 'query', 'SELECT cid, c_orden, c_nombre, c_seo, c_img FROM p_categorias ORDER BY c_orden'));
	}

	/**
	 * @access public
	 * @param string $key
	 * @return array|string
	 */
	public function getTema(string $key = ''): array|string {
		$data = db_exec([__FILE__, __LINE__], 'query', "SELECT t_name, t_path, t_copy FROM w_temas WHERE t_path = :tema LIMIT 1", [
			'tema' => $this->settings['tema'] ?: 'default'
		]);
		return empty($key) ? $data : $data[$key];
	}

	/**
	 * @access public
	 * @return array
	 */
   	public function getNews() {
	  	$data = [];
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT not_body, not_color FROM w_noticias WHERE not_active = :active ORDER by RAND()', ['active' => 1]);
		while($row = db_exec('fetch_assoc', $query)) {
			$row['not_body'] = $this->setBBCode($row['not_body'], 'news');
		 	$data[] = $row;
		}
	  	//
	  	return $data;
   	}

	/**
	 * @access public
	 * @return string
	 */
	public function existinstall(): string  {
		$dirs = ['/install', '/upgrade'];
		foreach($dirs as $dir) {
			if(is_dir( TS_ROOT . $dir )) {
				return "<div id=\"msg_{$dir}\">Por favor, elimine el directorio <strong>{$dir}</strong>";
			}
		}
		return '';
	}

   	/**
	 * @access public
	 * @param string $badWords
	 * @param bool $all
	 * @return string
	 */
	public function parseBadWords(string $badWords = '', bool $all = false): string {
		$isWhere = $all ? '' : "WHERE type = :type";
		$params = $all ? [] : ['type' => 0];
		$query = db_exec([__FILE__, __LINE__], 'fetch_all', "SELECT word, swop, method, type FROM w_badwords {$isWhere}", $params);
		// Recorremos las censuras
		foreach($query AS $badword) {
			// Desestructuramos
			['word' => $word, 'swop' => $swop, 'method' => $method, 'type' => $type] = $badword;
			$find = empty($method) ? $word : "{$word} ";
			$replace = ((int)$type === 1) ? "<img title=\"{$word}\" src=\"{$swop}\" />" : "{$swop} ";
			$badWords = str_ireplace($find, $replace, $badWords);
		}
		return $badWords;
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar tsUser->setLevel() en su lugar.
	*/
	public function setLevel(int|string $tsLevel, bool $msg = false) {
		global $tsUser;
		$this->Logger->deprecated('tsCore->setLevel','tsUser->setLevel');
		// Por si viene con entero, obtendremos el string de ese valor
		if(is_numeric($tsLevel)) {
			$niveles = [0 => 'todos', 1 => 'visitante', 2 => 'miembro', 3 => 'moderador', 4 => 'administrador'];
			$tsLevel = $niveles[(int)$tsLevel];
		}
		return $tsUser->setLevel($tsLevel, $msg);
	}

	/**
	 * @access public
	 * @param string $tsDir
	 * @return void
	 */
	public function redirectTo(string $tsDir = '/'): void {
	    // Si es '/' o está vacío, redirigimos a la URL base de los settings
	    if ($tsDir === '/' || trim($tsDir) === '') {
	        $url = $this->settings['url'] ?? '/';
	    } else {
	        // Decodificamos y limpiamos espacios o saltos de línea maliciosos
	        $url = trim(urldecode($tsDir));
	    }
	    // Enviamos la cabecera de redirección
	    header("Location: " . $url);
	    exit();
	}

	/**
	 * Obtiene el dominio principal del sitio (SLD + TLD) limpiando subdominios.
	 *
	 * @access public
	 * @return string
	 */
	public function getDomain(): string {
	    // Extraemos solo el host (ej. "://misitio.com" o "localhost")
	    $host = parse_url($this->settings['url'] ?? '', PHP_URL_HOST);
	    if (!$host) {
	        return '';
	    }
	    // Lo picamos por sus puntos
	    $parts = explode('.', $host);
	    $count = count($parts);
	    // Si no tiene puntos (ej. "localhost"), devolvemos el host tal cual
	    if ($count < 2) {
	        return $host;
	    }
	    // Soporte para extensiones dobles comunes (com.mx, co.uk, net.pe, org.es, etc.)
	    $doubleTlds = ['com', 'net', 'org', 'co', 'gob', 'edu', 'mil'];
	    if ($count > 2 && in_array($parts[$count - 2], $doubleTlds)) {
	        return $parts[$count - 3] . '.' . $parts[$count - 2] . '.' . $parts[$count - 1];
	    }
	    // Caso estándar: devuelve "dominio.com" ignorando subdominios superiores
	    return $parts[$count - 2] . '.' . $parts[$count - 1];
	}

	/**
	 * Obtiene la URL completa del sitio actual.
	 *
	 * @access public
	 * @param bool $encode Si es true, devuelve la URL lista para usar en parámetros query (urlencode).
	 * @return string
	 */
	public function currentUrl(bool $encode = false): string {
	    // Capturamos el esquema de forma segura
	    $scheme = Http::scheme();
	    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	    $uri = $_SERVER['REQUEST_URI'] ?? '/';
	    // Armamos la URL completa
	    $url = $scheme . '://' . $host . $uri;
	    // Retornamos codificada o limpia según se solicite
	    return $encode ? urlencode($url) : $url;
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar json_decode() o json_encode() en su lugar.
	*/
	public function setJSON(string $data = '', string $type = 'encode', bool $force = false): string {
		$this->Logger->deprecated('tsCore->setJSON','json_decode() o json_encode()');
		return ($type === 'encode') ? json_encode($data) : ($force ? json_decode($data, true) : json_decode($data));
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar Paginador::setPageLimit() en su lugar.
	*/
	public function setPageLimit(int $tsLimit, bool|string $start = false, int $tsMax = 0): string {
		$this->Logger->deprecated('tsCore->setPageLimit','Paginador::setPageLimit()');
		// Mantenemos por compatibilidad
		return Paginador::setPageLimit($tsLimit, $start, $tsMax);
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar Paginador::setMaximos() en su lugar.
	*/
	public function setMaximos(int $tsLimit, int $tsMax): bool {
		$this->Logger->deprecated('tsCore->setMaximos','Paginador::setMaximos()');
		// Mantenemos por compatibilidad
		return Paginador::setMaximos($tsLimit, $tsMax);
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar Paginador::getPages() en su lugar.
	*/
	public function getPages(int $tsTotal, int $tsLimit): array {
		$this->Logger->deprecated('tsCore->getPages','Paginador::getPages()');
		// Mantenemos por compatibilidad
		return Paginador::getPages($tsTotal, $tsLimit);
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar Paginador::getPagination() en su lugar.
	*/
	public function getPagination(int $total, int $per_page = 10): array {
		$this->Logger->deprecated('tsCore->getPagination','Paginador::getPagination()');
		// Mantenemos por compatibilidad
		return Paginador::getPagination($total, $per_page);
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar Paginador::pageIndex() en su lugar.
	*/
	public function pageIndex(string $base_url, int &$start, int $max_value, int $num_per_page, bool $flexible_start = false): string {
		$this->Logger->deprecated('tsCore->pageIndex','Paginador::pageIndex()');
		// Mantenemos por compatibilidad
		return Paginador::pageIndex($base_url, $start, $max_value, $num_per_page, $flexible_start);
	}

	/**
	 * Sanitiza una cadena de texto para su uso seguro en consultas SQL u outputs HTML.
	 * Optimizado para PHP 8.4+ / 8.5+
	 *
	 * @access public
	 * @param string $var Cadena a sanitizar.
	 * @param bool $xss Si es true, aplica protección contra Cross-Site Scripting (XSS).
	 * @return string
	 */
	public function setSecure(string $var = '', bool $xss = false): string {
	    // Eliminamos barras invertidas sobrantes si existen
	    $var = stripslashes($var);
	    // Escape seguro para SQL
	    $var = db_exec('real_escape_string', $var);
	    // Protección XSS moderna si se solicita
	    if ($xss) {
	        $var = htmlspecialchars($var, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	    }
	    return $var;
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar tsUser->antiFlood() en su lugar.
	*/
	public function antiFlood(bool $print = true, string $type = 'post', string $msg = '', ?int $limit = null): true|string {
		$this->Logger->deprecated('tsCore->antiFlood','tsUser->antiFlood()');
	    global $tsUser;
	    return $tsUser->antiFlood($print, $type, $msg, $limit);
	}

	/**
	 * @access public
	 * @param string $string
	 * @param array $options
	 * @return string
	 */
	public function setSEO(string $string, array $options = []): string {
		// Reutilización del código (DRY)
		return smarty_modifier_seo($string, $options);
	}

	/**
	 * @access public
	 * @param string $bbcode
	 * @param string $type
	 * @return string
	 */
	public function setBBCode(string $bbcode = '', string $type = 'normal'): string {
		// Inicializamos la clase
		$parser = new BBCode($this);
		// Seleccionar texto
		$parser->setText($bbcode);
		// Cargamos los botones
		$allButtons = $parser->bbcodeAllow();
		$restriction = match($type) {
			'firma' => array_slice($allButtons, 0, 11),
			'news', 'noticias' => array_slice($allButtons, 0, 5),
			default => $allButtons,
		};
		$parser->setRestriction($restriction);
		// Parsear menciones si el tipo es 'normal' o 'smiles'
		if ($type === 'normal' || $type === 'smiles') {
			$parser->parseMentions();
		}
		$parser->parseSmiles();
		return $parser->getAsHtml();
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar setBBCode() en su lugar.
	*/
	public function parseBBCode(string $bbcode = '', string $type = 'normal'): string {
		$this->Logger->deprecated('tsCore->parseBBCode','tsCore->setBBCode');
		// Mantenemos compatibilidad
		return $this->setBBCode($bbcode, $type);
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar tsUser->setMenciones() en su lugar.
	*/
	public function setMenciones(string $html = '') {
		global $tsUser;
		$this->Logger->deprecated('tsCore->setMenciones','tsUser->setMenciones');
		// Mantemos compatibilidad
		return $tsUser->setMenciones($html);
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar setBBCode() en su lugar.
	*/
	public function parseSmiles(string $bbcode): string {
		$this->Logger->deprecated('tsCore->parseSmiles','tsCore->setBBCode');
		return $this->setBBCode($bbcode, 'normal');
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar setBBCode() en su lugar.
	*/
	public function parseBBCodeFirma(string $bbcode): string {
		$this->Logger->deprecated('tsCore->parseBBCodeFirma','tsCore->setBBCode');
	   return $this->setBBCode($bbcode, 'firma');
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Deja de usar tsCore->setHace().
	*/
	public function setHace(int $fecha = 0, bool $show = false) {
		$this->Logger->removed('tsCore->setHace', 'PHPost LTS');
		require_once TS_SOURCES . '/extensions/plugins/modifier.hace.php';
		return smarty_modifier_hace($fecha, $show);
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Deja de usar HttpClient::get() en su lugar.
	*/
	public function getUrlContent(string $tsUrl): string {
		$this->Logger->deprecated('tsCore->getUrlContent','HttpClient::get');
		return HttpClient::get($tsUrl);
	}

	# Función para comprobar reCaptcha v3
	public function reCaptcha(string $publico = '') {
	   // call curl to POST request
	   $http = http_build_query([
		'secret' => $this->settings["skey"],
		'response' => $publico,
		'remoteip' => $this->getIP()
	   ]);
	   $init = curl_init();
	  curl_setopt($init, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
	  curl_setopt($init, CURLOPT_POST, 1);
	  curl_setopt($init, CURLOPT_POSTFIELDS, $http);
	  curl_setopt($init, CURLOPT_RETURNTRANSFER, true);
	  $response = curl_exec($init);
	  curl_close($init);
	  return json_decode($response, true);
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar IP::getIP() en su lugar.
	*/
	public function getIP() {
		$this->Logger->deprecated('tsCore->getIP','IP->getIP o tsCore->ip()');
		// Mantenemos compatibilidad
		return $this->ip();
	}

	/**
	 * Genera una cadena de asignación SQL para SET a partir de un array asociativo.
	 * Ejemplo: ['user_name' => 'Juan', 'user_age' => 25] -> "user_name = 'Juan', user_age = 25"
	 *
	 * @access public
	 * @param array $array Array asociativo [columna => valor]
	 * @param string $prefix opcional para las columnas
	 * @return string
	 */
	public function getIUP(array $array = [], string $prefix = ''): string {
	    $sets = [];
	    // Recorremos llave y valor directamente sin duplicar el array en memoria
	    foreach ($array as $field => $val) {
	        // Si es estrictamente un entero o un flotante, lo dejamos limpio, si no, lo tratamos como string seguro
	        if (is_int($val) || is_float($val)) {
	            $va_ = $val;
	        } else {
	            // Pasamos TODO lo demás por seguridad y le ponemos comillas fijas
	            $safeVal = $this->setSecure((string)$val);
	            $va_ = "'{$safeVal}'";
	        }
	        $sets[] = $prefix . $field . " = " . $va_;
	    }
	    return implode(', ', $sets);
	}

}