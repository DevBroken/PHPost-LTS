<?php

declare(strict_types=1);

/**
 * tsVisitas - Registro de visitas de contenidos (extensible)
 *
 * Centraliza la lógica que estaba duplicada en tres clases:
 *   - c.posts.php  (getPost)     tipo 2
 *   - c.cuenta.php (loadHeadInfo) tipo 1
 *   - c.fotos.php  (getFoto)      tipo 3
 *
 * Lo que hace, en orden:
 *   1) comprueba si el contenido ya fue visto (miembro: por usuario o por IP;
 *      invitado: solo por IP),
 *   2) guarda la fila en w_visitas, o refresca fecha/IP si ya existía,
 *   3) suma el contador de hits de la tabla del contenido (nunca el del dueño).
 *
 * Cada tipo de contenido se declara con register(), así que sirve para posts,
 * usuarios, fotos, temas, comunidades o lo que se agregue en el futuro:
 *
 *   $tsVisitas->register('tema', 4, [
 *       'table' => 't_temas',
 *       'id'    => 'tema_id',
 *       'hits'  => 'tema_hits',
 *       'owner' => 'tema_user',
 *   ]);
 *   $tsVisitas->add('tema', $tema_id, $tema_user);
 *
 * @author PHPost Risus LTS
 * @copyright 2027
 */

defined('TS_HEADER') OR exit('No se permite el acceso directo al script');

class tsVisitas {

    /** Códigos fijos en la base (w_visitas.type). No cambiar: los usan datos viejos. */
    public const USUARIO = 1;
    public const POST    = 2;
    public const FOTO    = 3;

    protected tsCore $tsCore;
    protected tsUser $tsUser;

    /** Tipos registrados: nombre => configuración. */
    private array $types = [];

    public function __construct(tsCore $tsCore, tsUser $tsUser)
    {
        $this->tsCore = $tsCore;
        $this->tsUser = $tsUser;

        // Tipos por defecto (con los códigos que ya existen en w_visitas)
        $this->register('usuario', self::USUARIO, [
            'table'       => 'u_miembros',
            'id'          => 'user_id',
            'hits'        => null,     // el contador de visitas del perfil se retiró en 1.1.000.9
            'owner'       => 'user_id',
            'record_own'  => false,    // no se registra la visita al propio perfil
        ]);
        $this->register('post', self::POST, [
            'table' => 'p_posts',
            'id'    => 'post_id',
            'hits'  => 'post_hits',
            'owner' => 'post_user',
        ]);
        $this->register('foto', self::FOTO, [
            'table' => 'f_fotos',
            'id'    => 'foto_id',
            'hits'  => 'f_hits',
            'owner' => 'f_user',
        ]);
    }

    /**
     * Declara (o sobrescribe) un tipo de contenido.
     *
     * @param string $name   Nombre del tipo: 'post', 'foto', 'usuario', 'tema'...
     * @param int    $code   Código numérico que viaja en w_visitas.type
     * @param array  $config Opciones:
     *                       table       -> tabla del contador (null = solo w_visitas)
     *                       id          -> columna identificadora del contenido
     *                       hits        -> columna a sumar (null = sin contador)
     *                       owner       -> columna del dueño (null = sin dueño)
     *                       record_own  -> ¿guardar la visita aunque sea del dueño?
     *                                      (el hit propio nunca se suma)
     *                       allow_guest -> ¿los invitados suman hit con c_hits_guest = 1?
     */
    public function register(string $name, int $code, array $config = []): void
    {
        $this->types[$name] = array_merge([
            'code'         => $code,
            'table'        => null,
            'id'           => null,
            'hits'         => null,
            'owner'        => null,
            'record_own'   => true,
            'allow_guest'  => true,
        ], $config);
    }

    /** ¿Existe el tipo registrado? */
    public function has(string $name): bool
    {
        return isset($this->types[$name]);
    }

    /** Código numérico de un tipo (null si no está registrado). */
    public function code(string $name): ?int
    {
        return isset($this->types[$name]) ? (int) $this->types[$name]['code'] : null;
    }

    /** Tipos registrados: nombre => configuración. */
    public function types(): array
    {
        return $this->types;
    }

    /**
     * Registra la visita del usuario actual sobre un contenido.
     *
     * @param string   $type  Tipo registrado ('post', 'foto', 'usuario'...)
     * @param int      $id    ID del contenido visitado
     * @param int|null $owner ID del dueño del contenido (para no contar hits propios
     *                        y, en tipos con record_own = false, no registrar la visita)
     * @return bool true si se creó una fila nueva en w_visitas
     */
    public function add(string $type, int $id, ?int $owner = null): bool
    {
        if ($id <= 0 || !isset($this->types[$type])) {
            return false;
        }
        $cfg = $this->types[$type];
        $code = (int) $cfg['code'];
        $uid = (int) $this->tsUser->uid;
        $isMember = (bool) $this->tsUser->is_member;
        $ip = $this->clientIp();
        $date = time();

        $visitado = $this->visited($id, $code, $ip);

        // MIEMBRO
        if ($isMember) {
            // La propia visita solo se guarda si el tipo lo permite
            if (!$visitado && ($owner === null || $uid !== $owner || !empty($cfg['record_own']))) {
                $this->insert($uid, $id, $code, $date, $ip);
                $this->countHit($cfg, $id, $uid, $owner !== null);
                return true;
            }
            // Visita repetida: solo refrescamos fecha/IP de la fila propia
            db_exec([__FILE__, __LINE__], 'query',
                "UPDATE `w_visitas` SET `date` = '{$date}', `ip` = '{$ip}'"
                . " WHERE `for` = '{$id}' AND `type` = '{$code}' AND `user` = '{$uid}' LIMIT 1");
            return false;
        }

        // INVITADO: solo si el sitio cuenta visitas de invitados
        if (!empty($cfg['allow_guest'])
            && (int) ($this->tsCore->settings['c_hits_guest'] ?? 0) === 1
            && !$visitado) {
            $this->insert(0, $id, $code, $date, $ip);
            $this->countHit($cfg, $id, 0, false);
            return true;
        }

        return false;
    }

    /**
     * Visitantes recientes de un contenido (w_visitas + datos del usuario).
     * Mismo resultado que las consultas que usaban cada clase por su cuenta.
     */
    public function recent(string $type, int $id, int $limit = 10, bool $onlyMembers = true): array
    {
        $code = $this->code($type);
        if ($code === null || $id <= 0) {
            return [];
        }
        $limit = max(1, (int) $limit);
        $members = $onlyMembers ? ' AND v.user > 0' : '';
        $query = db_exec([__FILE__, __LINE__], 'query',
            'SELECT v.*, u.user_id, u.user_name FROM `w_visitas` AS v'
            . ' LEFT JOIN `u_miembros` AS u ON v.user = u.user_id'
            . " WHERE v.for = '{$id}' AND v.type = '{$code}'{$members}"
            . " ORDER BY v.date DESC LIMIT {$limit}");
        return result_array($query);
    }

    /** Total de visitas registradas a un contenido. */
    public function total(string $type, int $id, bool $onlyMembers = false): int
    {
        $code = $this->code($type);
        if ($code === null || $id <= 0) {
            return 0;
        }
        $members = $onlyMembers ? ' AND v.user > 0' : '';
        $row = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query',
            'SELECT COUNT(v.id) FROM `w_visitas` AS v'
            . " WHERE v.for = '{$id}' AND v.type = '{$code}'{$members}"));
        return (int) ($row[0] ?? 0);
    }

    /** ¿Ya existe una visita de este usuario/IP a este contenido? */
    private function visited(int $id, int $code, string $ip): bool
    {
        $where = $this->tsUser->is_member
            ? "(`user` = '{$this->tsUser->uid}' OR `ip` = '{$ip}')"
            : "`ip` = '{$ip}'";
        $num = db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query',
            "SELECT id FROM `w_visitas` WHERE `for` = '{$id}' AND `type` = '{$code}' AND {$where} LIMIT 1"));
        return (int) $num > 0;
    }

    /** INSERT en w_visitas. */
    private function insert(int $user, int $id, int $code, int $date, string $ip): void
    {
        db_exec([__FILE__, __LINE__], 'query',
            'INSERT INTO `w_visitas` (`user`, `for`, `type`, `date`, `ip`)'
            . " VALUES ('{$user}', '{$id}', '{$code}', '{$date}', '{$ip}')");
    }

    /**
     * Suma +1 al contador del contenido (nunca si lo visitó su dueño).
     * $filterOwner solo aplica si el tipo tiene columna de dueño.
     */
    private function countHit(array $cfg, int $id, int $uid, bool $filterOwner): void
    {
        if (empty($cfg['table']) || empty($cfg['id']) || empty($cfg['hits'])) {
            return; // Tipo sin contador (p. ej. usuarios)
        }
        $sql = "UPDATE `{$cfg['table']}` SET `{$cfg['hits']}` = `{$cfg['hits']}` + 1"
            . " WHERE `{$cfg['id']}` = '{$id}'";
        if ($filterOwner && !empty($cfg['owner'])) {
            $sql .= " AND `{$cfg['owner']}` <> '{$uid}'";
        }
        db_exec([__FILE__, __LINE__], 'query', $sql);
    }

    /** IP del cliente (misma que venía usando el código original). */
    private function clientIp(): string
    {
        $ip = (string) $this->tsCore->ip();
        if ($ip === '' || $ip === 'unknown') {
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        }
        return $ip;
    }
}
