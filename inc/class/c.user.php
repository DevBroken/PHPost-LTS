<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

defined('TS_HEADER') OR exit('No se permite el acceso directo al script');

require_once TS_CLASS . "/c.session.php";

class tsUser {

	public mixed $info = [];
	public array $permisos = [];

	public int $is_member = 0;
	public int $is_admod = 0;
	public int $is_banned = 0;
	public int $uid = 0;

	public string $nick = 'Visitante';

	public mixed $is_error;

	protected tsCore $tsCore;
	protected tsSession $tsSession;

	public function __construct(tsCore $tsCore, tsSession $tsSession) {
		$this->tsCore = $tsCore;
		$this->tsSession = $tsSession;
		$this->setSession();
		# Esta logueado, actualiza puntos por día
		if($this->is_member) {
			$this->actualizarPuntos();
		}
	}

	/**
	 * @access public
	 * @return void
	 */
	public function setSession(): void {
		// Si no existe una sessión la creamos, si existe la actualizamos...
		if (!$this->tsSession->read()) $this->tsSession->create();
		else {
			// Actualizamos sesión
			$this->tsSession->update();
			// Cargamos información
			$this->loadUser();
		}
	}

	/**
	 * @access public
	 * @param string $tsLevel: 0 todos | 1 visitantes | 2 miembros | 3 moderador |4 administrador
	 * @param bool $message
	 * @return array|bool
	 */
	public function setLevel(string $tsLevel = 'todos', bool $message = false): array|bool {
		// Los mensajes
		$setMessages = [
			'todos' => 'Área pública.',
			'visitante' => 'Esta página solo es vista por los visitantes.',
			'miembro' => 'Para poder ver esta página debes iniciar sesión.',
			'moderador' => 'Estas en un área solo para moderadores.',
			'administrador' => 'Estas en un área solo para administradores.'
		];
		// Definimos los accesos!
		$conditions = [
			'todos' => true, // CUALQUIERA
			'visitantes' => $this->is_member === 0, // SOLO VISITANTES
			'miembros' => $this->is_member === 1, // SOLO MIEMBROS
			'moderador' => $this->is_admod || $this->hasUserPermission('moacp'), // SOLO MODERADORES
			'administrador' => $this->is_admod === 1 // SOLO ADMIN
		];
		if (!isset($conditions[$tsLevel])) {
			$tsLevel = 'todos';
		}
		if($message && !$conditions[$tsLevel]) {
			// Manejo de mensajes de error
			return [
				'titulo' => 'Error',
				'mensaje' => $setMessages[$tsLevel] ?? 'Error desconocido.'
			];
		}
		return $conditions[$tsLevel];
	}

	/**
	 * @access public
	 * @return bool
	 */
	private function actualizarPuntos(): bool {
		$ultimaRecarga = (int)$this->info['user_nextpuntos'];
		$tiempoActual = time();
		// Si aún no ha llegado el momento de la recarga, salimos temprano (Early Return)
		if ($ultimaRecarga >= $tiempoActual) {
			return false;
		}
		$sigRecarga = strtotime('tomorrow midnight', $tiempoActual);
		if ((int)$this->tsCore->settings['c_keep_points'] === 0) {
			$puntosSql = (int)$this->permisos['gopfd'];
		} else {
			$puntosSql = 'user_puntosxdar + ' . (int)$this->permisos['gopfd'];
		}
		db_exec([__FILE__, __LINE__], 'query', "UPDATE u_miembros SET user_puntosxdar = {$puntosSql}, user_nextpuntos = {$sigRecarga} WHERE user_id = {$this->uid}");
		return true;
	}

	/**
	 * @access private
	 * @param int $uid
	 * @return void
	 */
	private function setRango(int $uid = 0): void {
		// PERMISOS SEGUN RANGO
		$this->info['rango'] = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT r_name, r_color, r_image, r_allows FROM u_rangos WHERE rango_id = {$uid} LIMIT 1"));

		// PERMISOS SEGUN RANGO
		$allows = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT r_allows FROM u_rangos WHERE rango_id = '{$this->info['user_rango']}' LIMIT 1"));

		$this->permisos = json_decode($allows['r_allows'], true);

		/* ES MIEMBRO */
		$this->is_member = 1;
		// suad (Super Administrador) || sumo (Super Moderador)
		$this->is_admod = match (true) {
			// 1 - Administrador puro
			!$this->permisos['sumo'] && $this->permisos['suad'] => 1,
			// 2 - Moderador puro
			$this->permisos['sumo'] && !$this->permisos['suad'] => 2,
			// 0 - Usuario normal o visitante
			default => 0,
		};
	}

	/**
	 * @access public
	 * @return bool
	 */
	public function hasPermisson(): bool {
		$permisosAValidar = [
			$this->permisos['moacp'], $this->permisos['most'], $this->permisos['moayca'],
			$this->permisos['mosu'], $this->permisos['modu'], $this->permisos['moep'],
			$this->permisos['moop'], $this->permisos['moedcopo'], $this->permisos['moaydcp'],
			$this->permisos['moecp']
		];
		if($this->is_admod || in_array(true, $permisosAValidar, true)) {
			return true;
		}
		return false;
	}

	/**
	 * @access public
	 * @param string $permission
	 * @return bool
	 */
	public function hasUserPermission(string $permission = ''): bool {
		// Si es administrador, tiene todos los permisos por defecto
		if ($this->is_admod) {
			return true;
		}
		// Si no es admin, verificamos si tiene asignado el permiso específico
		return !empty($this->permisos[$permission]);
	}

	/**
	 * @access public
	 * @return bool
	 */
	public function canAccessModMode(): bool {
		$isAdmod = $this->is_admod;
		$iSeeMod = (int)$this->tsCore->settings['c_see_mod'] === 1;
		return ($isAdmod AND $iSeeMod);
	}

	/**
	 * @access public
	 * @param int $user
	 * @return array
	 */
	public function isUserBlocked(int $user): array {
		$query = db_exec([__FILE__, __LINE__], 'query', "SELECT bid FROM u_bloqueos WHERE b_user = {$user} AND b_auser = {$this->uid} LIMIT 1");
		return db_exec('num_rows', $query);
	}

	/**
	 * @access public
	 * @return bool
	 */
	public function isUserIPBlocked(): bool {
		$query = db_exec([__FILE__, __LINE__], 'query', "SELECT id FROM w_blacklist WHERE type = 1 AND value = :value LIMIT 1", ['value' => $this->tsCore->ip('binary')]);
		return db_exec('num_rows', $query) === 1;
	}

	/**
	 * @access public
	 */
	public function loadUser(bool $login = false) {
		// Cargar datos (no dejamos nada al azar)
		$sql = "SELECT u.user_id, u.user_name, u.user_password, u.user_password_hash, u.user_email, u.user_rango, u.user_puntos, u.user_posts, u.user_comentarios, u.user_seguidores, u.user_cache, u.user_puntosxdar, u.user_bad_hits, u.user_nextpuntos, u.user_registro, u.user_lastlogin, u.user_lastactive, u.user_lastpost, u.user_last_ip, u.user_name_changes, u.user_activo, u.user_vip, u.user_baneado, s.session_id, s.session_user_id, s.session_ip, s.session_time, s.session_autologin FROM u_sessions s, u_miembros u WHERE s.session_id = '{$this->tsSession->ID}' AND u.user_id = s.session_user_id";
		$query = db_exec([__FILE__, __LINE__], 'query', $sql);
		$this->info = db_exec('fetch_assoc', $query);
		// Existe el usuario?
		if(!isset($this->info['user_id'])) return false;
		$this->setRango();
		// NOMBRE
		$this->nick 		= (string)$this->info['user_name'];
		$this->uid 			= (int)$this->info['user_id'];
		$this->is_banned 	= (int)$this->info['user_baneado'];
		// ULTIMA ACCION
		$time = time();
		db_exec([__FILE__, __LINE__], 'query', "UPDATE u_miembros SET user_lastactive = {$time} WHERE user_id = {$this->uid}");
		// Si ha iniciado sesión cargamos estos datos.
		if($login) {
			// Registramos la ultima IP y logueo
			db_exec([__FILE__, __LINE__], 'query', "UPDATE u_miembros SET user_last_ip = '{$this->tsSession->ip_address}', user_lastlogin = {$this->tsSession->time_now} WHERE user_id = {$this->uid}");
		}
		// Borrar variable session
		unset($this->tsSession);
	}

	/**
	 * @access private
	 * Verifica la contraseña de un usuario.
	 * Orden: user_password_hash (vigente, password_hash) -> user_password (md5 legacy).
	 * Si la clave es correcta y el hash es legacy o está desactualizado,
	 * se guarda el hash moderno en user_password_hash (migración transparente).
	 * @param string $password
	 * @param string $user
	 * @param array $data
	 * @return string '' si es correcta, o el mensaje de error '0: ...'.
	 */
	private function checkPassword(string $password, string $user, array $data): string {
		$userId		= (int)$data['user_id'];
		// Elegimos qué hash usar: priorizamos el nuevo, si está vacío usamos el legacy
		$isLegacy = empty($data['user_password_hash']) ? 'password' : 'password_hash';
		$currentHash = (string)($data["user_{$isLegacy}"] ?? '');
		// Hacemos la verificación una sola vez
    	$check = Password::verifyWithMigration($password, $user, $currentHash);
    	if (!$check['ok']) {
        	return '0: Tu contrase&ntilde;a es incorrecta.';
    	}
    	// Si requiere migración (ya sea por algoritmo viejo o por actualización del nuevo)
		if ($check['migrate']) {
			$newHash = Password::hash($password);
			db_exec([__FILE__, __LINE__], 'query', "UPDATE u_miembros SET user_password_hash = '{$newHash}' WHERE user_id = {$userId}");
		}
		return '';
	}

	/**
	 * @access public
	 * @param string $username
	 * @param string $password
	 * @param bool $remember
	 * @param string $redirectTo
	 * @return string|bool
	 */
	public function loginUser(string $username, string $password, bool $remember = false, string $redirectTo = '/'): string|bool
	{
		$username = strtolower($username);
		# Filtramos si es nombre o email
		$filter = (filter_var($username, FILTER_VALIDATE_EMAIL)) ? 'email' : 'name';
		# Consultamos
		$data = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT user_id, user_name, user_password, user_password_hash, user_activo, user_baneado FROM u_miembros WHERE LOWER(user_{$filter}) = '{$username}' LIMIT 1"));
		# Comprobamos que el usuario exista
		if(empty($data)) return '0: El usuario no existe.';
		# Comprobamos la contraseña (moderna o legacy md5 -> migra sola)
		$user = ($filter === 'email') ? strtolower($data["user_name"]) : $username;
		$error = $this->checkPassword($password, $user, $data);
		# Devolvemos el error si la clave es incorrecta (antes se ignoraba)
		if($error !== '') return $error;
		# Comprobamos que el usuario este activo
		if((int)$data['user_activo'] === 0) {
			return '0: Debes activar tu cuenta';
		}
		// Actualizamos la session
		$this->tsSession->update((int)$data['user_id'], $remember, TRUE);
		// Cargamos la información del usuario
		$this->loadUser(true);
		// COMPROBAMOS SI TENEMOS QUE ASIGNAR MEDALLAS
		$this->DarMedalla();
		/* REDERIGIR */
		if($redirectTo !== '/') {
			$this->tsCore->redirectTo($redirectTo);
		}
		return TRUE;
	}

	/**
	 * @access public
	 * @param int $user_id
	 * @param string $redirectTo
	 * @return void
	 */
	public function logoutUser(int $user_id, string $redirectTo = '/'): void {
		/* BORRAR SESSION */
		$this->tsSession->read();
		$this->tsSession->destroy();
		/* LIMPIAR VARIABLES */
		$this->info = '';
		$this->is_member = 0;
		// Calcular tiempo de inactividad
		$c_last_active = (int)($this->tsCore->settings['c_last_active'] ?? 0);
    	$last_active = (int)(time() - ($c_last_active * 60 * 3));
    	// Forzamos el cast!
    	$safe_user_id = (int)$user_id;
		# UPDATE
		$last_active = (time() - (((int)$this->tsCore->settings['c_last_active'] * 60) * 3));
		db_exec([__FILE__, __LINE__], 'query', "UPDATE u_miembros SET user_lastactive = {$last_active} WHERE user_id = {$safe_user_id}");
		/* REDERIGIR */
		$this->tsCore->redirectTo($redirectTo);
		exit;
	}

	/**
	 * @access public
	 * @param int $tsUserID
	 * @param int $tsKey
	 * @return bool
	 */
	public function userActivate(int $tsUserID = 0, int $tsKey = 0): bool {
		if(empty($tsUserID)) $tsUserID = (int)$_GET['uid'];
		if(empty($tsKey)) $tsKey = $this->tsCore->setSecure($_GET['key']);
		//
		$query = db_exec([__FILE__, __LINE__], 'query', "SELECT user_id, user_name, user_password, user_password_hash, user_registro FROM u_miembros WHERE user_id = {$tsUserID} LIMIT 1");
		$tsData = db_exec('fetch_assoc', $query);
		$tsKeyLocal = md5($tsData['user_registro']);
		//
		if(db_exec('num_rows', $query) == 0 || $tsKey != $tsKeyLocal){
			return false;
		} else {
			if(db_exec([__FILE__, __LINE__], 'query', 'UPDATE u_miembros SET user_activo = 1 WHERE user_id = \''.$tsUserID.'\'')) {
				return $tsData;
			}
			else return false;
		}
	}
	/*
		Abre sesión tras una activación de cuenta.
		No se puede usar loginUser() aquí: solo quedan hashes (password_hash o md5 legacy),
		nunca la clave en claro, así que iniciamos la sesión directamente.
	*/
	public function loginAfterActivation(array $data): bool {
		if(empty($data['user_id'])) return false;
		$this->tsSession->update((int)$data['user_id'], true, TRUE);
		$this->loadUser(true);
		$this->DarMedalla();
		return true;
	}
	/*
		getUserBanned()
	*/    function getUserBanned(){
		//
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT * FROM u_suspension WHERE user_id = \''.$this->uid.'\' LIMIT 1');
		$data = db_exec('fetch_assoc', $query);
		if($data === null) {
			return '';
		}
		//
		$now = time();
		//
		if($data['susp_termina'] > 1 && $data['susp_termina'] < $now){
			db_exec([__FILE__, __LINE__], 'query', 'UPDATE u_miembros SET user_baneado = 0 WHERE user_id = \''.$this->uid.'\'');
			db_exec([__FILE__, __LINE__], 'query', 'DELETE FROM u_suspension WHERE user_id = \''.$this->uid.'\'');
			return false;
		} else return $data;
	}
	/*
		getUserID($tsUsername)
	*/
	function getUserID($tsUser){
	global $tsCore;
		//
		$tsUsername = strtolower($tsUser);
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT user_id FROM u_miembros WHERE LOWER(user_name) = \''.$tsCore->setSecure($tsUsername).'\' LIMIT 1');
		$tsUser = db_exec('fetch_assoc', $query);

		$tsUserID = $tsUser['user_id'];
		//
		return empty($tsUserID) ? 0 : $tsUserID;
	}
	/*
		getUserName($user_id)
	*/
	function getUserName($user_id){
		//
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT user_name FROM u_miembros WHERE user_id = \''.(int)$user_id.'\' LIMIT 1');
		$tsUser = db_exec('fetch_assoc', $query);

		//
		return $tsUser['user_name'];
	}
	/**
	 * @name iFollow
	 * @access public
	 * @param int
	 * @return void
	 */
	public function iFollow($user_id){
		# SIGO A ESTE USUARIO
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT follow_id FROM u_follows WHERE f_id = \''.(int)$user_id.'\' AND f_user = \''.$this->uid.'\' AND f_type = \'1\' LIMIT 1');
		$data = db_exec('num_rows', $query);

		//
		return ($data > 0) ? true : false;
	}

	/**
	 * @name setMenciones
	 * @access public
	 * @param string
	 * @return string
	 */
	public function setMenciones(string $html): string {
		if(empty($html)) return '';
		# BUSCAMOS A USUARIOS
		preg_match_all('/\B@([a-zA-Z0-9_-]{4,16}+)\b/',$html, $users);
		$menciones = $users[1];
		# VEMOS CUALES EXISTEN
		foreach($menciones as $user) {
			$uid = $this->getUserID($user);
			if(!empty($uid)) {
				$find = "@{$user}";
				$replace = "@<a href=\"{$this->tsCore->settings['url']}/perfil/{$user}\">{$user}</a>";
				$html = str_replace($find, $replace, $html);
			}
		}
		# RETORNAMOS
		return $html;
	}

	/**
	 * Evita que un usuario realice demasiadas acciones en poco tiempo (Anti-Flood).
	 * Optimizado para PHP 8.4+ / 8.5+
	 *
	 * @param bool $print Si es true, detiene la ejecución con die(). Si no, retorna el mensaje.
	 * @param string $type Tipo de acción a validar (ej. 'post', 'comment', 'login').
	 * @param string $msg Mensaje personalizado de error.
	 * @param int|null $limit Tiempo límite en segundos. Si es null, se intenta leer del objeto de usuario.
	 * @return true|string Devuelve true si pasa el filtro, o el mensaje de error si es bloqueado.
	 */
	public function antiFlood(bool $print = true, string $type = 'post', string $msg = '', ?int $limit = null): true|string {
	    $now = time();
	    $msg = empty($msg) ? 'No puedes realizar tantas acciones en tan poco tiempo.' : $msg;
	    // Si no se pasa un límite, intentamos obtenerlo de la propiedad de la clase o la variable global de forma segura
	    if ($limit === null) {
	        $limit = (int)($this->permisos['goaf'] ?? 0);
	    }
	    // Inicializamos la sesión para este tipo de flood si no existe
	    if (!isset($_SESSION['flood'][$type])) {
	        $_SESSION['flood'][$type] = $now;
	        return true;
	    }
	    $lastAction = (int)$_SESSION['flood'][$type];
	    $resta = $now - $lastAction;
	    // Si el tiempo transcurrido es menor al límite, bloqueamos la acción
	    if ($resta < $limit) {
	        $seg = $limit - $resta;
	        $errorMsg = "0: {$msg} Int&eacute;ntalo en {$seg} segundos.";
	        if ($print) {
	            die($errorMsg);
	        }
	        return $errorMsg;
	    }
	    // CORRECCIÓN CRÍTICA: Si pasó el tiempo del flood, actualizamos SIEMPRE con el tiempo actual
	    $_SESSION['flood'][$type] = $now;
	    return true;
	}

	/**
	 * @name getVCard
	 * @access public
	 * @param int
	 * @return array
	 * @info OBTIENE LA INFORMACION DE UN USUARIO PARA UNA VCARD
	 */
	public function getVCard($user_id){
		# GLOBALES
		global $tsCore;
		# LOCALES
		$is_online = (time() - ($tsCore->settings['c_last_active'] * 60));
		$is_inactive = (time() - (($tsCore->settings['c_last_active'] * 60) * 2)); // DOBLE DEL ONLINE
		// INFORMACION GENERAL
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT u.user_id, u.user_name, u.user_lastactive, u.user_baneado, p.user_sexo, p.user_pais, p.p_nombre, p.p_mensaje, p.p_sitio FROM u_miembros AS u, u_perfil AS p WHERE u.user_id = \''.(int)$user_id.'\' AND p.user_id = \''.(int)$user_id.'\'');
		$data = db_exec('fetch_assoc', $query);

		//  STATS
		$query =  db_exec([__FILE__, __LINE__], 'query', 'SELECT u.user_puntos, r.r_name, r.r_color, r.r_image FROM u_miembros AS u LEFT JOIN u_rangos AS r ON u.user_rango = r.rango_id WHERE user_id = \''.(int)$user_id.'\' LIMIT 1');
		$data['stats'] = db_exec('fetch_assoc', $query);

		$q1 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(post_id) AS p FROM p_posts WHERE post_user = \''.(int)$user_id.'\' && post_status = \'1\''));
		$q2 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(cid) AS c FROM p_comentarios WHERE c_user = \''.(int)$user_id.'\' && c_status = \'1\''));
		$q3 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(follow_id) AS s FROM u_follows WHERE f_id = \''.(int)$user_id.'\' && f_type = \'1\''));

		$data['stats']['user_posts'] = $q1[0];
		$data['stats']['user_comentarios'] = $q2[0];
		$data['stats']['user_seguidores'] = $q3[0];
		// STATUS
		if($data['user_lastactive'] > $is_online) $data['status'] = array('t' => 'Online', 'css' => 'online');
		elseif($data['user_lastactive'] > $is_inactive) $data['status'] = array('t' => 'Inactivo', 'css' => 'inactive');
		else $data['status'] = array('t' => 'Offline', 'css' => 'offline');
		// SIGUIENDO
		$data['follow'] = $this->iFollow($user_id);
		//
		return $data;
	}
	/*++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/
							// FUNCIONES EXTERNAS \\
	/*++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/
	/*
		getUsuarios()
	*/
	function getUsuarios(){
		global $tsCore;
		// FILTROS ||
		$is_online = (time() - ($tsCore->settings['c_last_active'] * 60));
		$is_inactive = (time() - (($tsCore->settings['c_last_active'] * 60) * 2)); // DOBLE DEL ONLINE
		// ONLINE?
		if($_GET['online'] == 'true'){
			$w_online = 'AND u.user_lastactive > '.$is_online.'';
		}
		// CON FOTO
		if($_GET['avatar'] == 'true'){
			$w_avatar = 'AND p.p_avatar = 1';
		}
		// SEXO
		if(!empty($_GET['sexo'])){
			$sex = ($_GET['sexo'] == 'f') ? 0 : 1;
			$w_sex = '&& p.user_sexo = \''.$sex.'\'';
		}
		// PAIS
		if(!empty($_GET['pais'])){
			$pais = $tsCore->setSecure($_GET['pais']);
			$w_pais = '&& p.user_pais = \''.$pais.'\'';
		}
		// STAFF
		if(!empty($_GET['rango'])){
			$rango = (int)$_GET['rango'];
			$w_rango = '&& u.user_rango = '.$rango.'';
		}
		// TOTAL Y PAGINAS
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(u.user_id) AS total FROM u_miembros AS u LEFT JOIN u_perfil AS p ON u.user_id = p.user_id WHERE u.user_activo = \'1\' && u.user_baneado = \'0\' '.$w_online.' '.$w_avatar.' '.$w_sex.' '.$w_pais.' '.$w_rango);
		$total = db_exec('fetch_assoc', $query);
		$total = $total['total'];

		$pages = $tsCore->getPagination($total, 12);
		// CONSULTA
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT u.user_id, u.user_name, p.user_pais, p.user_sexo, p.p_avatar, p.p_mensaje, u.user_rango, u.user_puntos, u.user_comentarios, u.user_posts, u.user_lastactive, u.user_baneado, r.r_name, r.r_color, r.r_image FROM u_miembros AS u LEFT JOIN u_perfil AS p ON u.user_id = p.user_id LEFT JOIN u_rangos AS r ON r.rango_id = u.user_rango WHERE u.user_activo = \'1\' && u.user_baneado = \'0\' '.$w_online.' '.$w_avatar.' '.$w_sex.' '.$w_pais.' '.$w_rango.'  ORDER BY u.user_id DESC LIMIT '.$pages['limit']);
		// PARA ASIGNAR SI ESTA ONLINE HACEMOS LO SIGUIENTE
		while($row = db_exec('fetch_assoc', $query)){
			if($row['user_lastactive'] > $is_online) $row['status'] = array('t' => 'Online', 'css' => 'online');
			elseif($row['user_lastactive'] > $is_inactive) $row['status'] = array('t' => 'Inactivo', 'css' => 'inactive');
			else $row['status'] = array('t' => 'Offline', 'css' => 'offline');
			// RANGO
			$row['rango'] = array('title' => $row['r_name'], 'color' => $row['r_color'], 'image' => $row['r_image']);
			// CARGAMOS
			$data[] = $row;
		}

		// ACTUALES
		$total = explode(',',$pages['limit']);
		$total = ($total[0]) + count($data);
		//
		return array('data' => $data, 'pages' => $pages, 'total' => $total);
	}
	/*
		DarMedalla()
	*/
	function DarMedalla(){
		//
	  $q1 = db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', 'SELECT wm.medal_id FROM w_medallas AS wm LEFT JOIN w_medallas_assign AS wma ON wm.medal_id = wma.medal_id WHERE wm.m_type = \'1\' AND wma.medal_for = \''.$this->uid.'\''));
		$q2 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(follow_id) AS f FROM u_follows WHERE f_id = \''.$this->uid.'\' && f_type = \'1\''));
		$q3 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(follow_id) AS s FROM u_follows WHERE f_user = \''.$this->uid.'\' && f_type = \'1\''));
		$q4 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(cid) AS c FROM p_comentarios WHERE c_user = \''.$this->uid.'\' && c_status = \'1\''));
		$q5 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(cid) AS cf FROM f_comentarios WHERE c_user = \''.$this->uid.'\''));
		$q6 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(foto_id) AS fo FROM f_fotos WHERE f_status = \'1\' && f_user = \''.$this->uid.'\''));
		$q7 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(post_id) AS p FROM p_posts WHERE post_user = \''.$this->uid.'\' && post_status = \'1\''));
		// MEDALLAS
		$datamedal = result_array($query = db_exec([__FILE__, __LINE__], 'query', 'SELECT medal_id, m_cant, m_cond_user, m_cond_user_rango FROM w_medallas WHERE m_type = \'1\' ORDER BY m_cant DESC'));
		//
		foreach($datamedal as $medalla){
			// DarMedalla
			if($medalla['m_cond_user'] == 1 && !empty($this->info['user_puntos']) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $this->info['user_puntos']){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_user'] == 2 && !empty($q2[0]) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $q2[0]){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_user'] == 3 && !empty($q3[0]) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $q3[0]){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_user'] == 4 && !empty($q4[0]) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $q4[0]){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_user'] == 5 && !empty($q5[0]) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $q5[0]){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_user'] == 6 && !empty($q7[0]) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $q7[0]){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_user'] == 7 && !empty($q6[0]) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $q6[0]){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_user'] == 8 && !empty($q1) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $q1){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_user'] == 9 && !empty($this->info['user_rango']) && $medalla['m_cant'] > 0 && $medalla['m_cond_user_rango'] == $this->info['user_rango']){
				$newmedalla = $medalla['medal_id'];
			}
			//SI HAY NUEVA MEDALLA, HACEMOS LAS CONSULTAS
			if(!empty($newmedalla)){
				if(!db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', 'SELECT id FROM w_medallas_assign WHERE medal_id = \''.(int)$newmedalla.'\' && medal_for = \''.$this->uid.'\''))) {
					db_exec([__FILE__, __LINE__], 'query', 'INSERT INTO `w_medallas_assign` (`medal_id`, `medal_for`, `medal_date`, `medal_ip`) VALUES (\''.(int)$newmedalla.'\', \''.$this->uid.'\', \''.time().'\', \''.$_SERVER['REMOTE_ADDR'].'\')');
					db_exec([__FILE__, __LINE__], 'query', 'INSERT INTO u_monitor (user_id, obj_uno, not_type, not_date) VALUES (\''.$this->uid.'\', \''.(int)$newmedalla.'\', \'15\', \''.time().'\')');
					db_exec([__FILE__, __LINE__], 'query', 'UPDATE w_medallas SET m_total = m_total + 1 WHERE medal_id = \''.(int)$newmedalla.'\'');
				}
			}
	   }
	}
}