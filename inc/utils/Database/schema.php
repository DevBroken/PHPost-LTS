<?php

declare(strict_types=1);

/**
 * Adaptación del esquema a la versión del servidor — PHPost Risus LTS
 *
 * /inc/utils/database/collections.php define el esquema "moderno" (MySQL 8).
 * Este archivo permite ejecutarlo sin problemas en servidores más antiguos
 * o más estrictos, sin tocar datos ni perder funcionalidad:
 *
 *  - Columnas JSON:  `JSON ... DEFAULT (JSON_OBJECT(...))` solo si el servidor
 *                    lo admite (MySQL >= 8.0.13). Si no, la misma columna pasa
 *    a `VARCHAR(n) ... DEFAULT '<json en texto>'`, que es válido en todas las
 *    versiones (MySQL no permite DEFAULT literal sobre JSON: ERROR 1101).
 *  - Índices con columna DESC: se omite el DESC si no se soporta (MySQL < 8).
 *  - FULLTEXT sobre InnoDB: se omite si la versión no lo permite (MySQL < 5.6).
 *  - Claves de más de 767 bytes: se acortan con prefijo (191) en utf8mb4.
 *
 * El esquema en collections.php usa marcadores {{JSON:nombre}}; el SQL final
 * se resuelve con phpostSchemaBuild() justo antes de ejecutarlo, cuando ya
 * conocemos el servidor real.
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

// Ya cargado (collections.php lo incluye con require_once)
if (function_exists('phpostSchemaBuild')) {
    return;
}

/**
 * Capacidades del servidor a partir de SELECT VERSION().
 *
 * Ojo con el default: si la versión no se puede interpretar se asume el modo
 * "conservador" (compatible con todo). Peor rendimiento de esquema, pero
 * instala en cualquier sitio en lugar de fallar.
 */
function phpostSchemaCapsFromVersion(string $version): array
{
    $v = trim($version);
    $isMaria = stripos($v, 'mariadb') !== false;

    preg_match('/(\d+)\.(\d+)(?:\.(\d+))?/', $v, $m);
    $major = (int) ($m[1] ?? 0);
    $minor = (int) ($m[2] ?? 0);
    $patch = (int) ($m[3] ?? 0);
    // 8.0.13 -> 80013 | 10.6.12 -> 100612
    $num = ($major * 10000) + ($minor * 100) + $patch;

    if ($isMaria) {
        return [
            'version'     => $v,
            'flavor'      => 'mariadb',
            'json_default' => false, // JSON es alias de LONGTEXT: sin DEFAULT fiables
            'desc_index'  => $num >= 100600,
            'fulltext'    => $num >= 50500,
            'key767'      => true,   // prefijo por si innodb_large_prefix está OFF
        ];
    }

    return [
        'version'     => $v,
        'flavor'      => 'mysql',
        'json_default' => $num >= 80013, // DEFAULT sobre JSON (expresión entre paréntesis)
        'desc_index'  => $num >= 80000,
        'fulltext'    => $num >= 50600,  // FULLTEXT en InnoDB
        'key767'      => $num < 80000,   // 8.0+: 3072 bytes sin prefijo
    ];
}

/**
 * Capacidades del servidor conectado. Con $pdo = null devuelve el modo
 * conservador (por eso todo punto de ejecución real pasa su conexión).
 * El resultado se memoriza por conexión: solo se consulta VERSION() una vez.
 */
function phpostSchemaCaps(?PDO $pdo = null): array
{
    if ($pdo === null) {
        return phpostSchemaCapsFromVersion('');
    }

    static $cache = [];
    $id = spl_object_id($pdo);
    if (!isset($cache[$id])) {
        $version = '';
        try {
            $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        } catch (Throwable) {
            $version = '';
        }
        $cache[$id] = phpostSchemaCapsFromVersion($version);
    }

    return $cache[$id];
}

/**
 * Expresión SQL equivalente a un valor PHP (para DEFAULT (JSON_OBJECT(...))).
 * Escapa comillas simples: se usa dentro de cadenas SQL de una sola comilla.
 */
function phpostJsonExpr(mixed $value): string
{
    // Objeto vacío -> {}  (array vacío en PHP no distingue [] de {})
    if (is_object($value)) {
        $value = (array) $value;
        if ($value === []) {
            return 'JSON_OBJECT()';
        }
        $parts = [];
        foreach ($value as $k => $v) {
            $parts[] = phpostJsonExpr((string) $k) . ', ' . phpostJsonExpr($v);
        }
        return 'JSON_OBJECT(' . implode(', ', $parts) . ')';
    }

    if (is_array($value)) {
        if ($value === []) {
            return 'JSON_ARRAY()';
        }
        if (array_is_list($value)) {
            return 'JSON_ARRAY(' . implode(', ', array_map(phpostJsonExpr(...), $value)) . ')';
        }
        $parts = [];
        foreach ($value as $k => $v) {
            $parts[] = phpostJsonExpr((string) $k) . ', ' . phpostJsonExpr($v);
        }
        return 'JSON_OBJECT(' . implode(', ', $parts) . ')';
    }
    if (is_bool($value)) {
        return $value ? 'TRUE' : 'FALSE';
    }
    if ($value === null) {
        return 'NULL';
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    return "'" . str_replace("'", "''", (string) $value) . "'";
}

/**
 * Registra una columna JSON del esquema y devuelve su marcador {{JSON:nombre}}.
 * Se registran las DOS formas (expresión y literal) para poder elegir según
 * el servidor en el momento de ejecutar.
 *
 * @param array|object $default Valor por defecto (se serializa a JSON; usa
 *                              (object)[] si necesitas un objeto vacío {}).
 * @param int          $width   Ancho para el modo compatible VARCHAR (siempre
 *                              en caracteres; el límite de fila es por bytes).
 */
function phpostJsonRegister(string $name, array|object $default, int $width): string
{
    $literal = json_encode($default, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($literal)) {
        $literal = '{}';
    }
    $GLOBALS['phpostJsonColumns'][$name] = [
        'literal' => $literal,
        'expr'    => phpostJsonExpr($default),
        'width'   => max(64, $width),
    ];

    return '{{JSON:' . $name . '}}';
}

/** Definición (tipo + default) de una columna JSON registrada, según servidor. */
function phpostJsonColumn(array $caps, string $name): string
{
    $col = $GLOBALS['phpostJsonColumns'][$name] ?? null;
    if ($col === null) {
        throw new RuntimeException("Columna JSON sin registrar: {$name}");
    }

    if (!empty($caps['json_default'])) {
        // MySQL >= 8.0.13: columna nativa JSON con default por expresión.
        return 'JSON NOT NULL DEFAULT (' . $col['expr'] . ')';
    }

    // Cualquier otro servidor: JSON no tiene DEFAULT (ERROR 1101), usamos
    // VARCHAR con el mismo valor por defecto como texto JSON.
    $literal = str_replace("'", "''", $col['literal']);
    return 'VARCHAR(' . $col['width'] . ") NOT NULL DEFAULT '{$literal}'";
}

/**
 * Acorta índices que superarían el límite de 767 bytes (utf8mb4 = 4 bytes por
 * carácter → prefijo 191). Solo se tocan definiciones KEY/INDEX, nunca datos.
 */
function phpostSchemaPrefixIndexes(string $sql, int $maxBytes = 767): string
{
    preg_match('/CHARSET=(\w+)/i', $sql, $cm);
    $charset = strtolower($cm[1] ?? 'utf8mb4');
    $bytes = match ($charset) {
        'utf8mb4'       => 4,
        'utf8', 'utf8mb3' => 3,
        default         => 1,
    };
    $prefixLen = intdiv($maxBytes, $bytes);
    if ($prefixLen < 1) {
        return $sql;
    }

    // Mapa columna -> tipo/longitud declarados en el propio CREATE
    $lengths = [];
    if (preg_match_all('/`(\w+)`\s+(VARCHAR|VARBINARY)\((\d+)\)/i', $sql, $m, PREG_SET_ORDER)) {
        foreach ($m as $c) {
            $lengths[$c[1]] = ['type' => strtoupper($c[2]), 'len' => (int) $c[3]];
        }
    }
    if ($lengths === []) {
        return $sql;
    }

    $out = preg_replace_callback(
        '/\b((?:UNIQUE|FULLTEXT|SPATIAL)\s+)?(KEY|INDEX)\s+(`?\w+`?)\s*\(([^)]*)\)/i',
        function (array $m) use ($lengths, $prefixLen, $bytes, $maxBytes): string {
            $specs = explode(',', $m[4]);
            foreach ($specs as $i => $spec) {
                $raw = trim($spec);
                // Separa el sufijo opcional ASC / DESC
                $tail = '';
                if (preg_match('/^(.*?)\s+(ASC|DESC)$/i', $raw, $sm)) {
                    $raw = $sm[1];
                    $tail = ' ' . strtoupper($sm[2]);
                }
                // Sin sufijo que preservar: "col ASC" ya separado arriba
                $name = trim($raw, " \t`");
                if ($name === '' || strpos($raw, '(') !== false || !isset($lengths[$name])) {
                    continue; // PRIMARY KEY, números, columnas cortas, prefijos ya puestos
                }
                $info = $lengths[$name];
                $colBytes = $info['len'] * ($info['type'] === 'VARBINARY' ? 1 : $bytes);
                if ($colBytes > $maxBytes && $prefixLen < $info['len']) {
                    $specs[$i] = "`{$name}`({$prefixLen})" . $tail;
                }
            }
            $keyword = ($m[1] !== '' ? strtoupper(trim($m[1])) . ' ' : '') . strtoupper($m[2]);
            return $keyword . ' ' . $m[3] . ' (' . implode(', ', $specs) . ')';
        },
        $sql
    );

    return is_string($out) ? $out : $sql;
}

/**
 * Convierte el esquema de collections.php al SQL ejecutable en el servidor.
 * Idempotente y solo sobre CREATE TABLE (los INSERT pasan intactos).
 */
function phpostSchemaBuild(string $sql, array $caps): string
{
    if (!str_starts_with(strtoupper(ltrim($sql)), 'CREATE')) {
        return $sql;
    }

    // 1) Columnas JSON marcadas -> JSON nativo o VARCHAR según servidor
    $sql = preg_replace_callback(
        '/\{\{JSON:(\w+)\}\}/',
        fn(array $m): string => phpostJsonColumn($caps, $m[1]),
        $sql
    ) ?? $sql;

    // 2) Índices descendentes: MySQL < 8.0 los ignora y otros fallan
    if (empty($caps['desc_index'])) {
        $sql = preg_replace('/\s+DESC(?=\s*[,)])/i', '', $sql) ?? $sql;
    }

    // 3) FULLTEXT en InnoDB no existe antes de MySQL 5.6
    if (empty($caps['fulltext'])) {
        $sql = preg_replace('/^[ \t]*FULLTEXT[^\n]*\n?/mi', '', $sql) ?? $sql;
        // Si el FULLTEXT era la última línea, la columna anterior queda con
        // una coma colgante: la quitamos (SQL inválido en cualquier versión).
        $sql = preg_replace('/,(\s*)\)/', '$1)', $sql) ?? $sql;
    }

    // 4) Límite de 767 bytes en claves
    if (!empty($caps['key767'])) {
        $sql = phpostSchemaPrefixIndexes($sql);
    }

    return $sql;
}

/**
 * Índices declarados dentro de un CREATE TABLE del esquema.
 *
 * Lo usa /upgrade para añadir a las tablas antiguas los índices que trae el
 * esquema moderno (en una instalación vieja las tablas existen pero sin ellos).
 * Formato de retorno: [tabla, nombre, tipo (INDEX|UNIQUE|FULLTEXT), columnas]
 *
 * @return array<int, array{0:string,1:string,2:string,3:string}>
 */
function phpostSchemaParseIndexes(string $sql): array
{
    if (!preg_match('/CREATE TABLE IF NOT EXISTS\s+`([^`]+)`/i', $sql, $t)) {
        return [];
    }
    $table = $t[1];
    $out = [];

    // FULLTEXT sin palabra clave KEY/INDEX: "FULLTEXT buscar_texto_en (a, b)"
    if (preg_match_all('/^[ \t]*FULLTEXT[ \t]+`?(\w+)`?[ \t]*\(([^)]*)\)/mi', $sql, $m, PREG_SET_ORDER)) {
        foreach ($m as $f) {
            $out[] = [$table, $f[1], 'FULLTEXT', phpostSchemaIndexColumns($f[2])];
        }
    }

    // KEY / INDEX / UNIQUE KEY / FULLTEXT INDEX
    $pattern = '/^[ \t]*(UNIQUE[ \t]+|FULLTEXT[ \t]+|SPATIAL[ \t]+)?(KEY|INDEX)[ \t]+`?(\w+)`?[ \t]*\(([^)]*)\)/mi';
    if (preg_match_all($pattern, $sql, $m, PREG_SET_ORDER)) {
        foreach ($m as $d) {
            $lead = strtoupper(str_replace([PHP_EOL, "\n", "\r", "\t"], ' ', $d[1] ?? ''));
            $type = match (true) {
                str_contains($lead, 'UNIQUE')   => 'UNIQUE',
                str_contains($lead, 'FULLTEXT') => 'FULLTEXT',
                str_contains($lead, 'SPATIAL')  => 'SPATIAL',
                default                         => 'INDEX',
            };
            $out[] = [$table, $d[3], $type, phpostSchemaIndexColumns($d[4])];
        }
    }

    return $out;
}

/** Normaliza "a, `b` DESC" -> "a,b" (comparables contra information_schema). */
function phpostSchemaIndexColumns(string $columns): string
{
    $parts = [];
    foreach (explode(',', $columns) as $col) {
        $col = preg_replace('/\s+(ASC|DESC)\b/i', '', trim($col)) ?? trim($col);
        $col = trim($col, " \t`");
        if ($col !== '') {
            $parts[] = $col;
        }
    }
    return implode(',', $parts);
}
