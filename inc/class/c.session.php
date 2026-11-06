<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

defined('TS_HEADER') OR exit('No se permite el acceso directo al script');

class tsSession {

    public string $ID = '';

    public int $sess_expiration = 7200;

    public bool $sess_match_ip = false;

    public int $sess_time_online = 300;

    public string $cookie_prefix = 'phpost_lts_';

    public string $cookie_name = 'lts_';

    public string $cookie_path = '/';

    public string $cookie_domain = '';

    public ?array $userdata = null;

    public string $ip_address;

    public int $time_now;

    public function __construct()
    {
        global $tsCore;

        $this->time_now = time();
        // Obtener el dominio o subdominio para la cookie
        $host = parse_url((string)$tsCore->settings['url'], PHP_URL_HOST);
        $host = str_replace('www.', '', strtolower($host ?? ''));
        // Establecer variables
        $this->cookie_domain = ($host === 'localhost') ? '' : '.' . $host;
        $this->cookie_name = $this->cookie_prefix . substr(md5($host), 0, 12);
        // Conversión manual segura de la IP a binario para la inserción
        $this->ip_address = $tsCore->ip('ip_binary');
        // Configuraciones del Administrador
        $this->sess_match_ip = (bool)$tsCore->allows['c_allow_sess_ip'];
        $this->sess_time_online = (int)$tsCore->settings['c_last_active'] === 0 ? $this->sess_time_online : ((int)$tsCore->settings['c_last_active'] * 60);
    }

    /**
     * Leer session activa
     *
     * @access  public
     * @return  bool
     */
    public function read(): bool
    {
        if (empty($_COOKIE[$this->cookie_name . '_sid'])) {
            return false;
        }

        $this->ID = (string)$_COOKIE[$this->cookie_name . '_sid'];

        // Validar longitud del nuevo ID criptográfico de 64 caracteres
        if (strlen($this->ID) !== 64) {
            return false;
        }

        // Obtener session desde la base de datos
        $session = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT session_id, session_user_id, session_ip AS session_ip, session_time, session_autologin FROM u_sessions WHERE session_id = '{$this->ID}'"));

        // Existe en la DB?
        if (!$session || !isset($session['session_id'])) {
            $this->destroy();
            return false;
        }

        // ¿La sesión ha expirado?
        if (($this->time_now - (int)$session['session_time']) > $this->sess_expiration && empty($session['session_autologin'])) {
            $this->destroy();
            return false;
        }

        // Si cambió de IP y está activo el chequeo, destruimos
        if ($this->sess_match_ip && $session['session_ip'] !== $this->ip_address) {
            $this->destroy();
            return false;
        }

        // Listo guardamos y retornamos
        $this->userdata = $session;
        return true;
    }

    /**
     * Create a new session
     *
     * @access  public
     * @return  void
     */
    public function create(): void
    {
        $this->ID = $this->gen_session_id();
        db_exec([__FILE__, __LINE__], 'query', "INSERT INTO u_sessions (session_id, session_user_id, session_ip, session_time, session_autologin) VALUES ('{$this->ID}', 0, '{$this->ip_address}', {$this->time_now}, 0)");
        $this->set_cookie('sid', $this->ID, $this->sess_expiration);
    }

    /**
     * Update an existing session
     *
     * @access  public
     * @return  void
     */
    public function update(int $user_id = 0, bool $autologin = false, bool $force_update = false): void
    {
        if (!$this->userdata) {
            return;
        }

        // Limitar escrituras innecesarias en DB mediante tiempo de actividad online
        if (((int)$this->userdata['session_time'] + $this->sess_time_online) >= $this->time_now && !$force_update) {
            return;
        }

        $userIdUpdate = ($user_id === 0) ? (int)$this->userdata['session_user_id'] : $user_id;
        $autologinVal = empty($this->userdata['session_autologin']) ? ($autologin ? 1 : 0) : (int)$this->userdata['session_autologin'];

        // Actualizar datos locales
        $this->userdata['session_user_id'] = $userIdUpdate;
        $this->userdata['session_ip'] = $this->ip_address;
        $this->userdata['session_time'] = $this->time_now;
        $this->userdata['session_autologin'] = $autologinVal;

        db_exec([__FILE__, __LINE__], 'query', "UPDATE u_sessions SET session_user_id = '{$userIdUpdate}', session_ip = '{$this->ip_address}', session_time = {$this->time_now}, session_autologin = {$autologinVal} WHERE session_id = '{$this->ID}'");

        // Garbage Collector aleatorio
        $this->sess_gc();
        // Calcular expiración de cookie | 1 año corregido a 31536000
        $expiration = ($autologinVal === 1) ? 31536000 : $this->sess_expiration;
        $this->set_cookie('sid', $this->ID, $expiration);
    }

    /**
     * Destroy the current session
     *
     * @access  public
     * @return  void
     */
    public function destroy(): void
    {
        if (!empty($this->ID)) {
            db_exec([__FILE__, __LINE__], 'query', "DELETE FROM u_sessions WHERE session_id = '{$this->ID}'");
        }
        $this->set_cookie('sid', '', -31536000);
        $this->userdata = null;
    }

    /**
     * Crear cookie
     * @access public
     * @return void
     */
    public function set_cookie(string $name, string $cookiedata, int $cookietime): void
    {
        $cookiename = rawurlencode($this->cookie_name . '_' . $name);

        // Estructura moderna usando arrays exigida en estándares actuales
        setcookie($cookiename, $cookiedata, [
            'expires'  => $this->time_now + $cookietime,
            'path'     => $this->cookie_path,
            'domain'   => $this->cookie_domain,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    /**
     * Generar un ID de sesión
     *
     * @access public
     * @return string
     */
    public function gen_session_id(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Eliminar sesiones expiradas
     *
     * @access  public
     * @return  void
     */
    public function sess_gc(): void
    {
        // Ejecución probabilística del 30%
        if (random_int(0, 99) < 30) {
            $expire = $this->time_now - $this->sess_expiration;
            db_exec([__FILE__, __LINE__], 'query', "DELETE FROM u_sessions WHERE session_time < {$expire} AND session_autologin = 0");
        }
    }
}