<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

defined('TS_HEADER') OR exit('No se permite el acceso directo al script');

require_once TS_UTILS . '/misc/recaptcha.php';

class tsRegistro {

	protected tsCore $tsCore;
	protected tsUser $tsUser;
	protected tsEmail $tsEmail;
	protected Logger $Logger;
	public array $register = [];
	public string $public_key = '';

	private array $errors = [
		'default'	=> 'El campo es requerido',
		'nickname' 	=>'El nombre de usuario ya se encuentra registrado.',
		'password' 	=> 'La contrase&ntilde;a tiene que ser distinta que el nick',
		'email' 	=> 'El formato es incorrecto',
		'captcha' 	=> 'Validaci&oacute;n incorrecta',
	];

	public function __construct(tsCore $tsCore, tsUser $tsUser, tsEmail $tsEmail, Logger $Logger)
	{
		$this->tsCore = $tsCore;
		$this->tsUser = $tsUser;
		$this->tsEmail = $tsEmail;
		$this->Logger = $Logger;
		$this->register = $this->setRegister();
		$this->public_key = $this->reCaptchaKeys('pkey');
	}

	/**
     * Extrae el dominio o el usuario de un email de forma segura.
     * @access private
     * @param string $email
     * @param bool $before_needle
     * @return string
     */
    private function strstr(string $email, bool $before_needle = false): string {
        $result = strstr($email, '@', $before_needle);
        if ($result === false) {
            return '';
        }
        if (!$before_needle) {
            $result = ltrim($result, '@');
        }
        return $this->tsCore->setSecure($result);
    }

	/**
	 * @access private
	 * @return string
	 */
	private function reCaptchaKeys(string $keyname): string {
		$register = db_exec([__FILE__, __LINE__], 'query', "SELECT pkey, skey FROM w_configuracion WHERE tscript_id = :id LIMIT 1", ['id' => 1]);
		if(!in_array($keyname, ['pkey', 'skey', 'turstile'], true)) {
			Response::json('0: Las claves no existen o estan mal escritas.');
		}
		return (string)$register[$keyname];
	}

	/**
	 * @access public
	 * @return array
	 */
	public function setRegister(): array {
		$register = db_exec([__FILE__, __LINE__], 'query', "SELECT c_reg_active, c_reg_activate, c_reg_rango, c_met_welcome, c_message_welcome, c_allow_edad FROM w_configuracion WHERE tscript_id = :id LIMIT 1", ['id' => 1]);
		return is_array($register) ? $register : [];
	}

	/**
     * Obtiene y sanitiza las variables del POST
     */
    private function getNicknameEmail(bool $lower = false): array {
    	$nick = trim($this->tsCore->parseBadWords($_POST['nickname'] ?? ''));
        return [
            'user_nick'  => $lower ? strtolower($nick) : $nick,
            'user_email' => trim(strtolower($_POST['email'] ?? ''))
        ];
    }

    /**
     * Comprueba si el Nickname o el Email ya existen de forma independiente
     */
	private function nickEmailExists(array $vars, string $which): string {
        $value = ($which === 'nickname') ? $vars['user_nick'] : $vars['user_email'];
        $column = ($which === 'nickname') ? 'user_name' : 'user_email';
        if (empty($value)) {
            return '';
        }
        $query = db_exec([__FILE__, __LINE__], 'fetch_all', "SELECT user_id FROM u_miembros WHERE LOWER({$column}) = :value LIMIT 1", [
            'value' => $value
        ]);
        if (count($query) > 0) {
            return "El {$which} ya se encuentra registrado.";
        }
        return '';
    }

	/**
     * @name checkUserEmail()
     * @access public
     * @return void
     */
	public function checkUserEmail(): void {
        $vars = $this->getNicknameEmail(true);
        $which = !empty($vars['user_email']) ? 'email' : 'nickname';
        $type = ($which === 'email') ? $vars['user_email'] : $vars['user_nick'];
        // Validamos
        if (empty($type)) {
            Response::error('0: Faltan datos y no se puede procesar tu solicitud.');
        }
        $registerCheck = $this->nickEmailExists($vars, $which);
        if (!empty($registerCheck)) {
            Response::error($registerCheck);
        }
        if (!empty($vars['user_email'])) {
            $domain = $this->strstr($type, false);
            $userAccount = $this->strstr($type, true);
            $blacklist = db_exec([__FILE__, __LINE__], 'fetch_all', "SELECT id FROM w_blacklist WHERE (type = 3 AND value = :domain) OR (type = 4 AND value = :user) OR (type = 4 AND value = :full_email) LIMIT 1", [
                    'domain' => $domain,
                    'user' => $userAccount,
                    'full_email' => $type
                ]
            );
            if (count($blacklist) > 0) {
                Response::error('0: Parte del email no está permitida.');
            }
        }
		$typeResponse = !empty($vars['user_email']) ? 'email' : 'nickname';
        Response::success("El {$typeResponse} está disponible.");
	}

	/**
	 * Valida el token de Google reCAPTCHA
	 *
	 * @param string $publico Token enviado desde el formulario del cliente
	 * @return array Resultado de la verificación de Google
	 */
	private function reCaptcha(string $publico = ''): array {
		try {
			$response = HttpClient::post("https://www.google.com/recaptcha/api/siteverify", [
				'secret'   => $this->reCaptchaKeys("skey"),
				'response' => $publico,
				'remoteip' => $this->tsCore->ip('human')
			]);
			return json_decode($response, true) ?? ['success' => false, 'error-codes' => ['json-parse-error']];
		} catch (Throwable $e) {
			$this->Logger->error($e->getMessage(), 'registro');
			// En caso de caída de conexión o problemas con el SSL de Google
			return ['success' => false, 'error-codes' => ['http-connection-failed']];
		}
	}

	private function verifyNotExists(array $data): void {
		$nick = strtolower($this->tsCore->setSecure($data['user_nick']));
		$email = strtolower($this->tsCore->setSecure($data['user_email']));
		// COMPROBAR NUEVAMENTE QUE EL USUARIO O EMAIL NO SE ENCUENTREN REGISTRADOS
		$rows = db_exec([__FILE__, __LINE__], 'query', "SELECT user_name,user_email FROM u_miembros WHERE LOWER(user_name) = :nick OR LOWER(user_email) = :email LIMIT 1", [
			'nick' => $nick,
			'email' => $email
		]);
		if ($rows !== NULL) {
			$this->Logger->error('nick/email registrado', 'registro');
			Response::error('Que haces, ya existe alguien registrado con ese nick/email');
		}
		if(!filter_var($data['user_email'], FILTER_VALIDATE_EMAIL)) {
			$this->Logger->error('email no válido', 'registro');
			Response::error('Formato de email no válido.');
		}
		// Haciendo chiste!
		if((int)$this->register['c_reg_active'] === 0) {
			$this->Logger->error('registro cerrado', 'registro');
			Response::error('Lo lamento el registro esta cerrado.');
		}
	}

	private function createAvatar(int $uid, string $nickname): void {
		$seed = urlencode($nickname);
		// Elegir un estilo de DiceBear (ej. 'identicon', 'bottts', 'adventurer', 'lorelei')
		$style = 'avataaars-neutral';
		$avatar = "https://api.dicebear.com/10.x/{$style}/svg?seed={$seed}";
		$imagen_avatar = file_get_contents($avatar);
		if ($imagen_avatar !== false) {
    		// Guardar el archivo en tu carpeta del servidor
    		$folder = TS_STORAGE . '/avatar/avatar_user_' . $uid;
    		if(!is_dir($folder)) {
    			mkdir($folder, 0755, true);
    		}
    		file_put_contents("{$folder}/avatar.svg", $imagen_avatar);
		}
	}

	private function setWelcome(int $uid, array $data): void {
		// MENSAJE PARA DAR LA BIENVENIDA BIENVENIDA
		$time = time();
		$send_welcome = (int)$this->register['c_met_welcome'];
		if($send_welcome > 0 && $send_welcome < 4) {
			$bienvenida = 'Bienvenid@';
			$mensaje = str_replace(
				['[usuario]', '[welcome]', '[web]'],
				[$data['user_nick'], $bienvenida, $this->tsCore->settings['titulo']],
				$this->tsCore->setBBCode($this->register['c_message_welcome'])
			);
			switch($send_welcome) {
				case 1:
					$m_id = db_exec([__FILE__, __LINE__], 'insert_id', "INSERT INTO u_muro (p_user, p_user_pub, p_date, p_body, p_type) VALUES (:uid, 1, :time, :mensaje, 1)", [
						'uid' => $uid,
						'time' => $time,
						'mensaje' => $mensaje
					]);
					db_exec([__FILE__, __LINE__], 'execute', "INSERT INTO u_monitor (user_id, obj_user, obj_uno, not_type, not_total, not_menubar, not_monitor) VALUES (:uid, 1, :mid, 12, 1, 1, 1)", [
						'uid' => $uid,
						'mid' => $m_id
					]);
				break;
				case 2:
					$preview = substr($mensaje, 0, 75);
					$mp_id = db_exec([__FILE__, __LINE__], 'insert_id', "INSERT INTO u_mensajes (mp_to, mp_from, mp_subject, mp_preview, mp_date) VALUES (:uid, 1, :from, :preview, :time)", [
						'uid' => $uid,
						'from' => "{$bienvenida} a {$this->tsCore->settings['titulo']}",
						'preview' => $preview,
						'time' => $time
					]);
					db_exec([__FILE__, __LINE__], 'execute', "INSERT INTO u_respuestas (mp_id, mr_from, mr_body, mr_ip, mr_date) VALUES (:mp_id, 1, :mensaje, :ip, :time)", [
						'mp_id' => $mp_id,
						'mensaje' => $mensaje,
						'ip' => $this->tsCore->ip('binary'),
						'time' => $time
					]);
				break;
				case 3:
					db_exec([__FILE__, __LINE__], 'execute', "INSERT INTO u_avisos (user_id, av_subject, av_body, av_date, av_type) VALUES (:uid, :subject, :time, 4)", [
						'uid' => $uid,
						'subject' => "{$bienvenida} a {$this->tsCore->settings['titulo']}",
						'time' => $time
					]);
				break;
			}
		}
	}

	private function sendEmail(int $uid, array $data): void {
		if((int)$this->register['c_reg_activate'] === 0) {
			$pin = strtoupper(bin2hex(random_bytes(4))); // ej: A9F3C8D2
			$pinHash = password_hash($pin, PASSWORD_DEFAULT);
			$time = time();
			$expires = $time + 900;

			$contacts = db_exec([__FILE__, __LINE__], 'insert_id', "INSERT INTO w_contacts (user_id, user_email, `time`, expire, type, hash) VALUES (:uid, :email, :time, :expire, 2, :pin)", [
				'uid' => $uid,
				'email' => $data['user_email'],
				'time' => $time,
				'expire' => $expires,
				'pin' => $pinHash
			]);

			if ($contacts !== NULL) {
				$this->tsEmail->setActionSend(
				    plantilla: 'activar_cuenta',
				    parametros: [
				        'USERNAME' => $data['user_nick'],
				        'PASSWORD' => $data['user_password'],
				        'PIN' => $pin
				    ],
				    usuario: [
				        'nick' => $data['user_nick'],
				        'email' => $data['user_email']
				    ]
				);
				if ($this->tsEmail->send()) {
				    Response::success("Te hemos enviado un correo a {$data['user_email']} con los &uacute;ltimos pasos para finalizar con el registro.<br><br>Si en los pr&oacute;ximos minutos no lo encuentras en tu bandeja de entrada, por favor, revisa tu carpeta de correo no deseado, es posible que se haya filtrado. Muchas gracias!");
				} else {
					$this->Logger->error('Hubo un error al intentar procesar lo solicitado', 'registro');
				    Response::error('Hubo un error al intentar procesar lo solicitado.');
				}
			} else {
				$this->Logger->error('Ocurrió un error, inténtelo de nuevo', 'registro');
				Response::error('Ocurrió un error, inténtelo de nuevo.');
			}
		} else {
			$this->tsUser->userActivate($uid, md5((string)$data['user_registro']));
			$this->tsUser->loginUser($data['user_nick'], $data['user_password'], true);
			Response::success("Bienvenido a {$this->tsCore->settings['titulo']}, Ahora estas registrado y tu cuenta ha sido activada, podrás disfrutar de esta comunidad inmediatamente. Muchas gracias!");
		}
	}

	/**
	 * Funcion para limpiar parametros, evitando los ajenos
	 * en caso de ser manipulado
	 */
	private function cleanParams(array $data): array {
		// Creamos el hash
		$params = array_merge($data, [
		    'user_password' => Password::hash($data['user_password']),
		    'user_last_ip'  => $this->tsCore->ip('binary')
		]);
		return array_intersect_key(
			$params,
			array_flip([
				'user_nick', 'user_password', 'user_email', 'user_rango', 'user_registro', 'user_last_ip'
			])
		);
	}

	/**
	 * @name registerUser()
	 * @access public
	 * @return void
	 */
	public function registerUser(): void {
		if(isset($_POST['artscript']) && !empty($_POST['artscript'])) {
			$this->Logger->error('Lo siento mucho, pero no puedes continuar', 'registro');
			Response::error("Lo siento mucho, pero no puedes continuar.");
		}
		$data = [
			...$this->getNicknameEmail(),
			'user_password' => $_POST['password'],
			'user_rango' => (int)($this->register['c_reg_rango'] ?? 3),
			'user_registro' => time()
		];
		if((bool)($_POST['terms'] ?? false) !== true) {
			$this->Logger->error('No has aceptados los términos aún', 'registro');
			Response::error('No has aceptados los términos aún.');
		}
		// Verificamos por si el front fue alterado
		foreach($data as $key => $val){
			if($val === '') {
				$key_error = str_replace('user_', '', $key);
				Response::error("{$key_error}: {$this->errors[$key_error]}");
			}
		}
		// Comprobamos el response
		$response = $this->reCaptcha((string)($_POST['response'] ?? ''));
		if(!$response['success']) {
			$this->Logger->error('recaptcha: No hemos podido validar tu humanidad', 'registro');
			Response::error('recaptcha: No hemos podido validar tu humanidad.');
		}
		// Comprobamos el nickname
		if(!preg_match("/^[a-zA-Z0-9_-]{4,20}$/", $data['user_nick'])) {
			$this->Logger->error('nick: Nombre de usuario inválido', 'registro');
			Response::error('nick: Nombre de usuario inválido.');
		}
		$this->verifyNotExists($data);
		$limpiarParametros = $this->cleanParams($data);
		// Insertamos el miembro (suena raro ja)
		$uid = db_exec([__FILE__, __LINE__], 'insert_id', "INSERT INTO u_miembros (user_name, user_password_hash, user_email, user_rango, user_registro, user_last_ip) VALUES (:user_nick, :user_password, :user_email, :user_rango, :user_registro, :user_last_ip)", $limpiarParametros);
		if($uid <= 0) {
			$this->Logger->error('Ocurrio un error, intentalo más tarde', 'registro');
			Response::error('Ocurrio un error, intentalo más tarde.');
		}
		db_exec([__FILE__, __LINE__], "execute", "INSERT INTO u_perfil (user_id, user_firma, p_socials) VALUES(:uid, '', '')", [
			'uid' => $uid
		]);
		db_exec([__FILE__, __LINE__], "execute", "INSERT INTO u_portal (user_id) VALUES(:uid)", [
			'uid' => $uid
		]);
		$this->createAvatar((int)$uid, (string)$data['user_nick']);
		$this->setWelcome((int)$uid, $data);
		$this->sendEmail((int)$uid, $data);
	}

}