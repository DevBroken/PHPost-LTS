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
	protected ?tsSession $tsSession = null;
	protected Logger $Logger;

	public function __construct(tsCore $tsCore, tsSession $tsSession, Logger $Logger) {
		$this->tsCore = $tsCore;
		$this->tsSession = $tsSession;
		$this->Logger = $Logger;
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
	 * @param string $level: 0 todos | 1 visitantes | 2 miembros | 3 moderador |4 administrador
	 * @return array|bool
	 */
	public function setLevel(string $level = 'todos'): array|bool {
		$conditions = [
			// CUALQUIERA
			'todos' => [
				'level' => true,
				'message' => '',
			],
			// SOLO VISITANTES
			'visitante' => [
				'level' => $this->is_member === 0,
				'message' => 'Esta página solo es vista por los visitantes.',
			],
			// SOLO MIEMBROS
			'miembro' => [
				'level' => $this->is_member === 1,
				'message' => 'Para poder ver esta página debes iniciar sesión.',
			],
			// SOLO MODERADORES
			'moderador' => [
				'level' => $this->is_admod || $this->hasUserPermission('moacp'),
				'message' => 'Estas en un área solo para moderadores.',
			],
			// SOLO ADMIN
			'administrador' => [
				'level' => $this->is_admod === 1,
				'message' => 'Estas en un área solo para administradores.'
			]
		];
		if (!isset($conditions[$level]['level'])) {
			$level = 'todos';
		}
		if(empty($conditions[$level]['level'])) {
			return true;
		}
		if(!$conditions[$level]['level']) {
			$this->Logger->error($setMessages[$level]['message'] ?? 'Error desconocido.', 'level');
			return [
				'titulo' => 'Error',
				'mensaje' => $setMessages[$level]['message'] ?? 'Error desconocido.'
			];
		}
		return $conditions[$level]['level'];
	}

	/**
	 * @access public
	 * @return bool
	 */
	private function actualizarPuntos(): bool {
		$ultimaRecarga = (int)$this->info['user_nextpuntos'];
		$tiempoActual = time();
		// Si aún no ha llegado el momento de la recarga, salimos temprano
		if ($ultimaRecarga >= $tiempoActual) {
			return false;
		}
		$sigRecarga = strtotime('tomorrow midnight', $tiempoActual);
		$puntosSql = ((int)$this->tsCore->settings['c_keep_points'] === 0) ?
			(int)$this->permisos['gopfd'] :
			'user_puntosxdar + ' . (int)$this->permisos['gopfd'];

		db_exec([__FILE__, __LINE__], 'execute', "UPDATE u_miembros SET user_puntosxdar = :puntosxdar, user_nextpuntos = :recarga WHERE user_id = :uid", [
			'puntosxdar' => $puntosSql,
			'recarga' => $sigRecarga,
			'uid' => $this->uid
		]);
		return true;
	}

	/**
	 * @access private
	 * @param int $rid
	 * @return void
	 */
	private function setRango(int $rid = 0): void {
		// PERMISOS SEGUN RANGO
		$this->info['rango'] = db_exec([__FILE__, __LINE__], 'fetch_assoc', "SELECT r_name, r_color, r_image, r_allows FROM u_rangos WHERE rango_id = :rid LIMIT 1", ['rid' => $rid]);
		$this->permisos = json_decode($this->info['rango']['r_allows'], true);
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
	 * @return bool
	 */
	public function isUserBlocked(int $user): bool {
	    $result = db_exec([__FILE__, __LINE__], 'fetch_assoc', "SELECT bid FROM u_bloqueos WHERE b_user = :buser AND b_auser = :bauser LIMIT 1", [
	        'buser' => $user,
	        'bauser' => $this->uid
	    ]);
	    return $result !== null;
	}

	/**
	 * @access public
	 * @return bool
	 */
	public function isUserIPBlocked(): bool {
	    $result = db_exec([__FILE__, __LINE__], 'fetch_assoc', "SELECT id FROM w_blacklist WHERE type = 1 AND value = :value LIMIT 1", [
	    	'value' => $this->tsCore->ip('binary')
	    ]);
	    return !empty($result);
	}

	/**
	 * @access public
	 */
	public function loadUser(bool $login = false) {
		if ($this->tsSession === null) {
	        return false;
	    }
		// Cargar datos (no dejamos nada al azar)
		$this->info = db_exec([__FILE__, __LINE__], 'fetch_assoc', "SELECT u.user_id, u.user_name, u.user_password, u.user_password_hash, u.user_email, u.user_rango, u.user_puntos, u.user_posts, u.user_comentarios, u.user_seguidores, u.user_cache, u.user_puntosxdar, u.user_bad_hits, u.user_nextpuntos, u.user_registro, u.user_lastlogin, u.user_lastactive, u.user_lastpost, u.user_last_ip, u.user_name_changes, u.user_activo, u.user_vip, u.user_baneado, s.session_id, s.session_user_id, s.session_ip, s.session_time, s.session_autologin FROM u_sessions s JOIN u_miembros u ON u.user_id = s.session_user_id WHERE s.session_id = :session_id AND u.user_id = s.session_user_id LIMIT 1", [
	        'session_id' => $this->tsSession->ID
	    ]);
		// Existe el usuario?
		if(!isset($this->info['user_id'])) return false;
		$this->setRango((int)$this->info['user_rango']);
		// NOMBRE
		$this->nick = (string)$this->info['user_name'];
		$this->uid = (int)$this->info['user_id'];
		$this->is_banned = (int)$this->info['user_baneado'];
		// ULTIMA ACCION
	    $update = "UPDATE u_miembros SET user_lastactive = :time";
	    $params = [
	        'time' => time(),
	        'uid' => $this->uid
	    ];
		if($login) {
	        // Registramos la última IP y logueo si corresponde
	        $update .= ", user_last_ip = :last_ip, user_lastlogin = :last_login";
	        $params['last_ip'] = $this->tsSession->ip_address;
	        $params['last_login'] = $this->tsSession->time_now;
		}
		$update .= " WHERE user_id = :uid";
		db_exec([__FILE__, __LINE__], 'execute', $update, $params);
		// Borrar variable session
		$this->tsSession = null;
	}

	/**
	 * @access private
	 * @param string $password
	 * @param string $user
	 * @param array $data
	 * @return string
	 */
	private function checkPassword(string $password, string $user, array $data): string {
		$userId = (int)$data['user_id'];
		// Elegimos qué hash usar: priorizamos el nuevo, si está vacío usamos el legacy
		$isLegacy = empty($data['user_password_hash']) ? 'password' : 'password_hash';
		$currentHash = (string)($data["user_{$isLegacy}"] ?? '');
		// Hacemos la verificación una sola vez
    	$check = Password::verifyWithMigration($password, $user, $currentHash);
    	if (!$check['ok']) {
        	return 'Tu usuario/contraseña no coinciden.';
    	}
    	// Si requiere migración (ya sea por algoritmo viejo o por actualización del nuevo)
		if ($check['migrate']) {
			$newHash = Password::hash($password);
			db_exec([__FILE__, __LINE__], 'execute', "UPDATE u_miembros SET user_password_hash = :hash WHERE user_id = :uid", [
				'hash' => $newHash,
				'uid' => $userId
			]);
		}
		return '';
	}

	/*
		Abre sesión tras una activación de cuenta.
		No se puede usar loginUser() aquí: solo quedan hashes (password_hash o md5 legacy),
		nunca la clave en claro, así que iniciamos la sesión directamente.
	*/
	public function loginAfterActivation(array $data, bool $remember = true, bool $force = true): bool {
		if(empty($data['user_id'])) return false;
		$this->tsSession->update((int)$data['user_id'], $remember, $force);
		$this->loadUser(true);
		$this->DarMedalla();
		return true;
	}

	/**
	 * @access public
	 * @param string $username
	 * @param string $password
	 * @param bool $remember
	 * @return void
	 */
	public function loginUser(string $username, string $password, bool $remember = false): void
	{
		if(empty($username) || empty($password)) Response::error('Faltan datos, formulario incompleto.');
		$username = strtolower($username);
		# Filtramos si es nombre o email
		$filter = (filter_var($username, FILTER_VALIDATE_EMAIL)) ? 'email' : 'name';
		# Consultamos
		$data = db_exec([__FILE__, __LINE__], 'fetch_assoc', "SELECT user_id, user_name, user_password, user_password_hash, user_activo, user_baneado FROM u_miembros WHERE LOWER(user_email) = :email OR LOWER(user_name) = :name LIMIT 1", ['email' => $username, 'name' => $username]);
		# Comprobamos que el usuario exista
		if(empty($data)) Response::error('El usuario no existe.');
		# Comprobamos la contraseña (moderna o legacy md5 -> migra sola)
		$user = ($filter === 'email') ? strtolower($data["user_name"]) : $username;
		$error = $this->checkPassword($password, $user, $data);
		# Devolvemos el error si la clave es incorrecta (antes se ignoraba)
		if($error !== '') Response::error($error);
		# Comprobamos que el usuario este activo
		if((int)$data['user_activo'] === 0) {
			Response::error('Debes activar tu cuenta');
		}
		// Actualizamos la session
		$this->loginAfterActivation($data, $remember, true);
		Response::success('Inicio sesión correctamente.');
	}

	/**
	 * @access private
	 * @return int
	 */
	private function last_user(): int {
		$c_last_active = (int)($this->tsCore->settings['c_last_active'] ?? 0);
    	$last_active = (int)(time() - ($c_last_active * 60 * 3));
    	return $last_active;
	}

	/**
	 * @access public
	 * @param int $user_id
	 * @return void
	 */
	public function logoutUser(int $user_id): void {
		/* BORRAR SESSION */
		$tsSession = new tsSession;
		$tsSession->read();
		$tsSession->destroy();
		/* LIMPIAR VARIABLES */
		$this->info = '';
		$this->is_member = 0;
		db_exec([__FILE__, __LINE__], 'execute', "UPDATE u_miembros SET user_lastactive = :active WHERE user_id = :uid", [
			'active' => $this->last_user(),
			'uid' => (int)$user_id
		]);
		/* REDERIGIR */
		$this->tsCore->navigate('/');
		exit;
	}

	/**
	 * @access public
	 * @param int $tsUserID
	 * @param string $tsKey
	 * @return bool|array
	 */
	public function userActivate(int $tsUserID = 0, string $tsKey = ''): bool|array {
		if(empty($tsUserID)) $tsUserID = (int)$_GET['uid'];
		if(empty($tsKey)) $tsKey = $this->tsCore->setSecure($_GET['key']);
		//
		$tsData = db_exec([__FILE__, __LINE__], 'query', "SELECT user_id, user_name, user_password, user_password_hash, user_registro FROM u_miembros WHERE user_id = :uid LIMIT 1", [
			'uid' => $tsUserID
		]);
		if(empty($tsData)) {
			return false;
		}
		$tsKeyLocal = md5((string)$tsData['user_registro']);
		//
		if($tsKey !== $tsKeyLocal) {
			return false;
		}
		$isUpdate = db_exec([__FILE__, __LINE__], 'execute', "UPDATE u_miembros SET user_activo = 1 WHERE user_id = :uid", [
			'uid' => $tsUserID
		]);
		if($isUpdate) {
			return $tsData;
		}
		return false;
	}

	/*
		getUserBanned()
	*/
	public function getUserBanned(): array|bool|string {
	    $data = db_exec([__FILE__, __LINE__], 'fetch_assoc', "SELECT susp_id, user_id, susp_causa, susp_date, susp_termina, susp_mod, susp_ip FROM u_suspension WHERE user_id = :uid LIMIT 1", ['uid' => $this->uid]);
	    // Si no está suspendido
	    if ($data === null) {
	        return '';
	    }
	    // Comprobamos si la suspensión ya expiró
	    if ((int)$data['susp_termina'] > 1 && (int)$data['susp_termina'] < time()) {
	    	// Actualizamos el estado baneado
	        db_exec([__FILE__, __LINE__], 'execute', "UPDATE u_miembros SET user_baneado = 0 WHERE user_id = :uid", ['uid' => $this->uid]);
	        // Eliminamos la suspension
	        db_exec([__FILE__, __LINE__], 'execute', "DELETE FROM u_suspension WHERE user_id = :uid", ['uid' => $this->uid]);
	        return false;
	    }
	    // Si sigue suspendido, devolvemos los datos de la suspensión
	    return $data;
	}

	/**
	 * @access public
	 * @param string $type 'id' obtener la id | 'name' obtener nick
	 * @param string|int $user user_id | user_name
	 * @return int|string retornara 'id' int | 'name' string
	 */
	public function getUserData(string $type, string|int $user): int|string {
		$isNumeric = is_int($user) || ctype_digit((string)$user);
    	$where = $isNumeric ? "user_id" : "LOWER(user_name)";
    	$value = $isNumeric ? (int)$user : strtolower((string)$user);
		$result = db_exec([__FILE__, __LINE__], 'fetch_assoc', "SELECT user_id, user_name FROM u_miembros WHERE $where = :search LIMIT 1", ['search' => $value]);
		return ($type === 'id') ? (int)($result['user_id'] ?? 0) : (string)($result['user_name'] ?? '');
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar tsUser->getUserData() en su lugar.
	*/
	public function getUserID(string $username): int {
		$this->Logger->deprecated('tsUser->getUserID','tsUser->getUserData');
		return $this->getUserData(type: 'id', user: $username);
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Usar tsUser->getUserData() en su lugar.
	*/
	public function getUserName(int $user_id): string {
		$this->Logger->deprecated('tsUser->getUserName','tsUser->getUserData');
		return $this->getUserData(type: 'name', user: $user_id);
	}

	/**
	 * @access public
	 * @param int $user_id
	 * @return bool
	 */
	public function iFollow(int $user_id): bool {
	    $result = db_exec([__FILE__, __LINE__], 'fetch_assoc', "SELECT follow_id FROM u_follows WHERE f_id = :fid AND f_user = :fuser AND f_type = 1 LIMIT 1", [
	   	    'fid'   => $user_id,
	   	    'fuser' => $this->uid
	   	]);
	    return ($result !== null);
	}

	/**
	 * @name setMenciones
	 * @access public
	 * @param string $html
	 * @return string
	 */
	public function setMenciones(string $html): string {
		if(empty($html)) return '';
		# BUSCAMOS A USUARIOS
		preg_match_all('/\B@([a-zA-Z0-9_-]{4,16}+)\b/',$html, $users);
		$menciones = $users[1];
		# VEMOS CUALES EXISTEN
		foreach($menciones as $user) {
			$uid = $this->getUserData(type: 'id', user: $user);
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
	 * @access public
	 * @param bool $print Si es true, detiene la ejecución con die(). Si no, retorna el mensaje.
	 * @param string $type Tipo de acción a validar (ej. 'post', 'comment', 'login').
	 * @param string $message Mensaje personalizado de error.
	 * @param int|null $limit Tiempo límite en segundos. Si es null, se intenta leer del objeto de usuario.
	 * @return true|string Devuelve true si pasa el filtro, o el mensaje de error si es bloqueado.
	 */
	public function antiFlood(bool $print = true, string $type = 'post', string $message = '', ?int $limit = null): true|string {
	    $now = time();
	    $message = $message ?? 'No puedes realizar tantas acciones en tan poco tiempo.';
	    //
	    if ($limit === null) {
	        $limit = (int)($this->permisos['goaf'] ?? 0);
	    }
	    // Inicializamos la sesión para este tipo de flood si no existe
	    if (!isset($_SESSION['flood'][$type])) {
	        $_SESSION['flood'][$type] = $now;
	        return true;
	    }
	    $lastAction = (int)$_SESSION['flood'][$type];
	    $resta = (int)($now - $lastAction);
	    // Si el tiempo transcurrido es menor al límite, bloqueamos la acción
	    if ($resta < $limit) {
	        $seg = $limit - $resta;
	        $errorMsg = "0: {$message} Int&eacute;ntalo en {$seg} segundos.";
	        if ($print) {
	            die($errorMsg);
	        }
	        return $errorMsg;
	    }
	    $_SESSION['flood'][$type] = $now;
	    return true;
	}

	/**
	 * @deprecated Desde la versión PHPost LTS. Ya no usara tsUser->getVCard().
	*/
	public function getVCard(int $user_id) {
		$this->Logger->removed('getVCard');
	}

	/**
	 * @access public
	 * @return array
	 */
	public function getUsuarios(): array {
	    $cLastActive = (int)($this->tsCore->settings['c_last_active'] ?? 5);
	    $isOnline    = time() - ($cLastActive * 60);
	    $isInactive  = time() - (($cLastActive * 60) * 2);
	    //
	    $whereClauses = [];
	    $params = [];
	    if (($_GET['online'] ?? '') === 'true') {
	        $whereClauses[] = "u.user_lastactive > :is_online";
	        $params['is_online'] = $isOnline;
	    }
	    if (!empty($_GET['sexo'])) {
	        $whereClauses[] = "p.user_sexo = :sexo";
	        $params['sexo'] = ($_GET['sexo'] === 'f') ? 0 : 1;
	    }
	    if (!empty($_GET['pais'])) {
	        $whereClauses[] = "p.user_pais = :pais";
	        $params['pais'] = (string)$_GET['pais'];
	    }
	    if (!empty($_GET['rango'])) {
	        $whereClauses[] = "u.user_rango = :rango";
	        $params['rango'] = (int)$_GET['rango'];
	    }
	    // Unimos los filtros adicionales si es que existen
	    $filterSql = !empty($whereClauses) ? ' AND ' . implode(' AND ', $whereClauses) : '';
	    // Obtener Total de Registros para el Paginador
	    $countResult = db_exec([__FILE__, __LINE__], 'fetch_assoc', "SELECT COUNT(u.user_id) AS total FROM u_miembros AS u LEFT JOIN u_perfil AS p ON u.user_id = p.user_id WHERE u.user_activo = 1 AND u.user_baneado = 0 {$filterSql}", $params);
	    $totalRecords = (int)($countResult['total'] ?? 0);
	    $pages = Paginador::fromRequest($totalRecords, 12);
	    // Consulta Principal de Usuarios
	    $usersList = db_exec([__FILE__, __LINE__], 'fetch_all', "SELECT u.user_id, u.user_name, p.user_pais, p.user_sexo, p.p_avatar, p.p_mensaje, u.user_rango, u.user_puntos, u.user_comentarios, u.user_posts, u.user_lastactive, u.user_baneado, r.r_name, r.r_color, r.r_image FROM u_miembros AS u LEFT JOIN u_perfil AS p ON u.user_id = p.user_id LEFT JOIN u_rangos AS r ON r.rango_id = u.user_rango WHERE u.user_activo = 1 AND u.user_baneado = 0 {$filterSql} ORDER BY u.user_id DESC LIMIT {$pages['limit']}", $params);
	    //
	    $data = [];
	    if (is_array($usersList)) {
	        foreach ($usersList as $row) {
	            $row['status'] = match (true) {
	                ($row['user_lastactive'] > $isOnline)   => ['t' => 'Online', 'css' => 'online'],
	                ($row['user_lastactive'] > $isInactive) => ['t' => 'Inactivo', 'css' => 'inactive'],
	                default                                 => ['t' => 'Offline', 'css' => 'offline']
	            };
	            $row['rango'] = ['title' => $row['r_name'], 'color' => $row['r_color'], 'image' => $row['r_image']];
	            $data[] = $row;
	        }
	    }
	    [$offset] = explode(',', $pages['limit']);
	    $currentTotal = (int)$offset + count($data);
	    return ['data' => $data, 'pages' => $pages, 'total' => $currentTotal];
	}

	/*
		DarMedalla(): No lo toco, voy a buscar otra forma para este caos :D
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