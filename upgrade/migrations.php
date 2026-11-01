<?php

declare(strict_types=1);

/**
 * PHPost Risus LTS - Migraciones de actualización
 *
 * Principios:
 * - Nunca borrar datos de usuario (sin DROP TABLE / sin TRUNCATE).
 * - CREATE TABLE IF NOT EXISTS para tablas nuevas.
 * - ADD COLUMN solo si no existe (information_schema).
 * - IPs: VARCHAR(15) -> VARBINARY(16) con INET6_ATON, conservando NULL en inválidas.
 * - Estados: *_status se invierten 0 <-> 1 (esquema viejo 0 = activo/publicado,
 *   esquema nuevo 1 = activo/publicado). Los valores 2, 3 u otros no se tocan.
 *   La inversión es atómica por tabla (un solo UPDATE con CASE) y queda marcada
 *   en storage/upgrade/ para no repetirla si vuelve a correr el actualizador.
 * - Passwords: MD5 es irreversible, NO se "desencripta". La columna vigente es
 *   u_miembros.user_password_hash (password_hash); user_password conserva el
 *   md5 legacy y el login migra sola la cuenta al primer acceso.
 * - Índices: se derivan del esquema de /install, así una base vieja queda
 *   igual que una instalación nueva (sin duplicados: se comprueba antes).
 *
 * Solo se basa en /inc/utils/Database/collections.php (adaptado al servidor
 * con /inc/utils/Database/schema.php).
 */

if (!defined('TS_HEADER')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso directo al script no permitido.');
}

// Adaptador multi-versión del esquema (define phost_schema_* y registra las
// columnas JSON del esquema). Cada job AJAX del upgrade es una petición HTTP
// aparte, así que no podemos depender de que otra función lo haya cargado antes
// (el job "indexes" llamaba a phpostSchemaCaps() sin que estuviese definida).
require_once dirname(__DIR__, 1) . '/inc/utils/database/collections.php';

/** Columnas IP a migrar: [tabla, columna] */
const UPGRADE_IP_COLUMNS = [
    ['f_fotos', 'f_ip'],
    ['f_comentarios', 'c_ip'],
    ['p_posts', 'post_ip'],
    ['p_borradores', 'b_ip'],
    ['p_comentarios', 'c_ip'],
    ['u_respuestas', 'mr_ip'],
    ['u_nicks', 'ip'],
    ['u_muro', 'p_ip'],
    ['u_muro_comentarios', 'c_ip'],
    ['u_sessions', 'session_ip'],
    ['u_suspension', 'susp_ip'],
    ['w_historial', 'mod_ip'],
    ['w_visitas', 'ip'],
    ['w_medallas_assign', 'medal_ip'],
    ['u_miembros', 'user_last_ip'],
];

/**
 * Columnas de estado a invertir (0 <-> 1): [tabla, columna].
 *
 * Esquema viejo: 0 = activo/publicado, 1 = oculto/desactivado.
 * Esquema nuevo: 1 = activo/publicado, 0 = oculto/desactivado.
 * Solo se tocan los valores 0 y 1; 2, 3 u otros quedan intactos.
 */
const UPGRADE_STATUS_COLUMNS = [
    ['p_posts', 'post_status'],
    ['p_comentarios', 'c_status'],
    ['f_fotos', 'f_status'],
    ['p_borradores', 'b_status'],
];

/**
 * Columnas nuevas / corregidas del esquema /install.
 * [tabla, columna, definición ADD/MODIFY]
 * Solo ADD si no existe; MODIFY solo en los casos tipados abajo.
 */
function upgrade_column_definitions(): array
{
    return [
        // Perfil: strict-mode (eran NOT NULL sin default -> 1364)
        ['u_perfil', 'user_firma', 'TEXT NULL DEFAULT NULL'],
        ['u_perfil', 'p_sitio', "VARCHAR(60) NOT NULL DEFAULT ''"],
        ['u_perfil', 'p_socials', 'TEXT NULL DEFAULT NULL'],
        // Perfil: novedades de /install
        ['u_perfil', 'user_avatar', "VARCHAR(255) NOT NULL DEFAULT 'avatar.png'"],
        ['u_perfil', 'user_portada', "VARCHAR(255) NOT NULL DEFAULT 'cover.png'"],
        ['u_perfil', 'user_modo', "ENUM('light','dark','dimmer') NOT NULL DEFAULT 'light'"],
        ['u_perfil', 'user_pais', "CHAR(2) NOT NULL DEFAULT 'XX'"],
        // Rangos: slug nuevo
        ['u_rangos', 'r_slug', "VARCHAR(32) NOT NULL DEFAULT ''"],
        // Borradores: causa era VARBINARY(128), ahora texto
        ['p_borradores', 'b_causa', 'VARCHAR(255) NULL DEFAULT NULL'],
        // Miembros: IP binaria nullable (era DEFAULT 0 inválido)
        ['u_miembros', 'user_last_ip', 'VARBINARY(16) NULL DEFAULT NULL'],
        // Configuracion: Añadir modo de paginación
        ['w_configuracion', 'c_type_paginator', "ENUM('paginas','ver_mas') NOT NULL DEFAULT 'paginas'"],
        // Posts & Borradores: Agregar post_slug
        ['p_posts', 'post_slug', "VARCHAR(255) NOT NULL DEFAULT ''"],
        ['p_borradores', 'b_slug', "VARCHAR(255) NOT NULL DEFAULT ''"],
        // Miembros: columna vigente para password_hash (user_password queda
        // como legacy md5 y no se borra: sirve para verificar cuentas viejas)
        ['u_miembros', 'user_password_hash', "VARCHAR(255) NOT NULL DEFAULT ''"]
    ];
}

/**
 * Índices a crear en tablas ya existentes.
 *
 * Se derivan del esquema de /install (collections.php): una base antigua
 * termina con los mismos índices que una instalación nueva. Añade aquí, si
 * hiciera falta, índices extra que no vengan en el CREATE:
 *
 *   ['p_posts', 'idx_ejemplo', 'INDEX', 'post_user, post_date'],
 *
 * Formato: [tabla, nombre_indice, tipo (INDEX|UNIQUE|FULLTEXT), columnas]
 * @return array<int, array{0:string,1:string,2:string,3:string}>
 */
function upgrade_index_definitions(?PDO $pdo = null): array
{
    $caps = phpostSchemaCaps($pdo);
    $out = [];
    // Se parsea el esquema YA adaptado al servidor: así los índices salen con
    // los prefijos/sin FULLTEXT igual que el CREATE que los crea en /install.
    foreach (upgrade_table_statements() as $table => $sql) {
        foreach (phpostSchemaParseIndexes(phpostSchemaBuild($sql, $caps)) as $idx) {
            $out[] = $idx;
        }
    }

    // Índices manuales que no están en el CREATE (van al final)
    $extra = [
        // ['p_posts', 'idx_ejemplo', 'INDEX', 'post_user, post_date'],
    ];

    // Salvaguarda: si el servidor no admite FULLTEXT, fuera (aunque venga del esquema)
    if (empty($caps['fulltext'])) {
        $out = array_values(array_filter($out, fn(array $i): bool => $i[2] !== 'FULLTEXT'));
    }

    return array_merge($out, $extra);
}

/** Esquema: copia local autónoma (no depende de /install, que suele borrarse). */
function upgrade_schema_file(): string
{
    $local = dirname(__DIR__, 1) . '/inc/utils/Database/collections.php';
    if (is_file($local)) {
        return $local;
    }
    return '';
}

/** Tablas del esquema nuevo (CREATE IF NOT EXISTS). */
function upgrade_table_statements(): array
{
    $collections = [];
    require upgrade_schema_file();
    $out = [];
    foreach ($collections as $name => $sql) {
        if (!str_starts_with(strtoupper(ltrim($sql)), 'CREATE')) {
            continue;
        }
        // Tabla real extraída del CREATE para el log
        if (preg_match('/CREATE TABLE IF NOT EXISTS `([^`]+)`/i', $sql, $m)) {
            $out[$m[1]] = $sql;
        } else {
            $out[$name] = $sql;
        }
    }
    return $out;
}

function upgrade_table_exists(PDO $pdo, string $table): bool
{
    try {
        $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
        return true;
    } catch (Throwable) {
        return false;
    }
}

function upgrade_column_info(PDO $pdo, string $db, string $table, string $column): ?array
{
    $st = $pdo->prepare(
        "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1"
    );
    $st->execute(['db' => $db, 't' => $table, 'c' => $column]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function upgrade_index_exists(PDO $pdo, string $db, string $table, string $indexName): bool
{
    $st = $pdo->prepare(
        "SELECT COUNT(*)
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND INDEX_NAME = :i"
    );
    $st->execute(['db' => $db, 't' => $table, 'i' => $indexName]);
    return (int)$st->fetchColumn() > 0;
}


/** 1. Tablas faltantes (sin tocar las existentes). */
function upgrade_run_tables(PDO $pdo): array
{
    $log = [];
    $caps = phpostSchemaCaps($pdo);
    foreach (upgrade_table_statements() as $table => $sql) {
        if (upgrade_table_exists($pdo, $table)) {
            $log[] = "[SKIP] Tabla {$table} ya existe, se conserva.";
            continue;
        }
        $pdo->exec(phpostSchemaBuild($sql, $caps));
        $log[] = "[OK] Se creó tabla {$table}.";
    }
    return $log;
}

/** 2. Columnas nuevas + correcciones strict-mode. */
function upgrade_run_columns(PDO $pdo, string $db): array
{
    $log = [];
    foreach (upgrade_column_definitions() as [$table, $col, $def]) {
        if (!upgrade_table_exists($pdo, $table)) {
            $log[] = "[SKIP] {$table}.{$col}: sin tabla, se creará en paso tablas.";
            continue;
        }
        $info = upgrade_column_info($pdo, $db, $table, $col);
        if ($info === null) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}");
            $log[] = "[OK] Se agregó {$table}.{$col}.";
            continue;
        }
        $log[] = "[SKIP] {$table}.{$col} ya existe ({$info['COLUMN_TYPE']}).";
    }
    // Correcciones de tipo que sí necesitan MODIFY (idempotentes con TRY):
    // (cargar collections.php registra las columnas JSON del esquema)
    $schema = upgrade_schema_file();
    if ($schema !== '') {
        require $schema;
    }
    $modifies = [];
    if (isset($GLOBALS['phpostJsonColumns']['p_configs'])) {
        // p_configs era VARCHAR(100) serializado, ahora JSON (o VARCHAR en el
        // modo compatible) con su default correcto según el servidor
        $modifies[] = 'ALTER TABLE `u_perfil` MODIFY `p_configs` '
            . phpostJsonColumn(phpostSchemaCaps($pdo), 'p_configs');
    }
    foreach ($modifies as $sql) {
        try {
            $pdo->exec($sql);
            $log[] = "[OK] MODIFY aplicado: " . substr($sql, 0, 70) . "...";
        } catch (Throwable $e) {
            $log[] = "[SKIP] MODIFY no aplicable (dato legacy o motor): " . $e->getMessage();
        }
    }
    return $log;
}

/** Columnas existentes de una tabla: [nombre => true]. */
function upgrade_table_columns(PDO $pdo, string $db, string $table): array
{
    $cols = [];
    try {
        $st = $pdo->prepare(
            "SELECT COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t"
        );
        $st->execute(['db' => $db, 't' => $table]);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $c) {
            $cols[(string) $c] = true;
        }
    } catch (Throwable) {
        // Sin permiso sobre information_schema: no filtramos (se comprobará al crear)
    }
    return $cols;
}

/** Índices existentes de una tabla: [nombre => ['cols', 'unique', 'fulltext']]. */
function upgrade_existing_indexes(PDO $pdo, string $db, string $table): array
{
    $map = [];
    try {
        $st = $pdo->prepare(
            "SELECT INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX, NON_UNIQUE, INDEX_TYPE
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t
             ORDER BY INDEX_NAME, SEQ_IN_INDEX"
        );
        $st->execute(['db' => $db, 't' => $table]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $name = (string) $row['INDEX_NAME'];
            if (!isset($map[$name])) {
                $map[$name] = [
                    'cols'     => '',
                    'unique'   => (int) $row['NON_UNIQUE'] === 0,
                    'fulltext' => strtoupper((string) $row['INDEX_TYPE']) === 'FULLTEXT',
                ];
            }
            $map[$name]['cols'] .= ($map[$name]['cols'] === '' ? '' : ',') . $row['COLUMN_NAME'];
        }
    } catch (Throwable) {
        // Sin permiso sobre information_schema: al menos comprobaremos por nombre
    }
    return $map;
}

/** "for,type,user" -> "`for`, `type`, `user`" (respeta prefijos `col`(191)). */
function upgrade_index_columns_sql(string $columns): string
{
    $out = [];
    foreach (explode(',', $columns) as $col) {
        $col = trim($col);
        if ($col === '') {
            continue;
        }
        if (preg_match('/^`?(\w+)`?\s*\((\d+)\)$/', $col, $m)) {
            $out[] = "`{$m[1]}`({$m[2]})";
        } else {
            $out[] = '`' . trim($col, '`') . '`';
        }
    }
    return implode(', ', $out);
}

/**
 * Plan de índices a crear en tablas ya existentes (idempotente y sin duplicar).
 *
 * Se comprueba por nombre Y por combinación de columnas: si la base vieja ya
 * tiene un índice equivalente con otro nombre, no se crea otro igual.
 * Lo comparten la pantalla de revisión (detect) y la ejecución real.
 *
 * @return array<int, array{table:string,name:string,type:string,cols:string,sql:string}>
 */
function upgrade_indexes_plan(PDO $pdo, string $db): array
{
    $byTable = [];
    foreach (upgrade_index_definitions($pdo) as [$table, $indexName, $type, $columns]) {
        $byTable[$table][] = [strtoupper($type), $indexName, $columns];
    }

    $plan = [];
    foreach ($byTable as $table => $indexes) {
        if (!upgrade_table_exists($pdo, $table)) {
            continue; // Sin tabla base: el CREATE de /install ya trae sus índices
        }
        $existing = upgrade_existing_indexes($pdo, $db, $table);
        $tableCols = upgrade_table_columns($pdo, $db, $table);

        foreach ($indexes as [$type, $indexName, $columns]) {
            $cols = phpostSchemaIndexColumns($columns);
            $isFulltext = ($type === 'FULLTEXT');

            if (isset($existing[$indexName])) {
                continue;
            }
            // Columnas que la tabla vieja no tiene (bases muy antiguas o
            // modificadas a mano): no se intenta el índice y no se reporta
            // como pendiente; si la columna llega en un paso posterior, una
            // re-ejecución del upgrade lo creará.
            if ($tableCols !== []) {
                $missingCol = false;
                foreach (explode(',', $cols) as $c) {
                    $base = preg_replace('/\(\d+\)$/', '', trim($c));
                    if ($base !== '' && !isset($tableCols[$base])) {
                        $missingCol = true;
                        break;
                    }
                }
                if ($missingCol) {
                    continue;
                }
            }
            // ¿Otro nombre con las mismas columnas, tipo y unicidad? No duplicar.
            $duplicate = false;
            foreach ($existing as $e) {
                if ($e['cols'] === $cols && $e['fulltext'] === $isFulltext && $e['unique'] === ($type === 'UNIQUE')) {
                    $duplicate = true;
                    break;
                }
            }
            if ($duplicate) {
                continue;
            }

            $typeSql = match ($type) {
                'UNIQUE'   => 'UNIQUE INDEX',
                'FULLTEXT' => 'FULLTEXT INDEX',
                'SPATIAL'  => 'SPATIAL INDEX',
                default    => 'INDEX',
            };
            $plan[] = [
                'table' => $table,
                'name'  => $indexName,
                'type'  => $type,
                'cols'  => $cols,
                'sql'   => "ALTER TABLE `{$table}` ADD {$typeSql} `{$indexName}` ("
                    . upgrade_index_columns_sql($cols) . ')',
            ];
            // Anotamos el recién planificado: evita duplicar dentro del mismo plan
            $existing[$indexName] = [
                'cols'     => $cols,
                'unique'   => $type === 'UNIQUE',
                'fulltext' => $isFulltext,
            ];
        }
    }

    return $plan;
}

/** 2b. Índices faltantes: los crea uno por uno según el plan. */
function upgrade_run_indexes(PDO $pdo, string $db): array
{
    $log = [];
    $plan = upgrade_indexes_plan($pdo, $db);
    $total = count(upgrade_index_definitions($pdo));
    $log[] = '[INFO] Índices del esquema: ' . count($plan)
        . " por crear de {$total} (los existentes se conservan).";

    foreach ($plan as $item) {
        try {
            $pdo->exec($item['sql']);
            $log[] = "[OK] Se creó el índice {$item['type']} '{$item['name']}' en {$item['table']} ({$item['cols']}).";
        } catch (Throwable $e) {
            $log[] = "[ERROR] No se pudo crear el índice {$item['name']} en {$item['table']}: " . $e->getMessage();
        }
    }

    return $log;
}


/**
 * 3. Passwords: columna vigente + reporte. NO convierte hashes (imposible).
 * user_password conserva el md5 legacy y user_password_hash recibe los
 * hashes de password_hash(); el login migra cada cuenta en su primer acceso.
 */
function upgrade_run_passwords(PDO $pdo, string $db): array
{
    $log = [];
    // Respaldo por si este paso se ejecuta de forma aislada (el paso "columns"
    // también la crea): la columna vigente es imprescindible para el login.
    $hashCol = upgrade_column_info($pdo, $db, 'u_miembros', 'user_password_hash');
    if ($hashCol === null) {
        $pdo->exec("ALTER TABLE `u_miembros` ADD COLUMN `user_password_hash` VARCHAR(255) NOT NULL DEFAULT ''");
        $log[] = "[OK] Se creó u_miembros.user_password_hash (columna vigente).";
    } else {
        $log[] = "[SKIP] user_password_hash ya existe ({$hashCol['COLUMN_TYPE']}).";
    }

    // Columna legacy: se ensancha solo si no admite los hashes largos que
    // versiones previas pudieron guardar ahí (bcrypt = 60 caracteres).
    $info = upgrade_column_info($pdo, $db, 'u_miembros', 'user_password');
    if ($info !== null && (int) filter_var($info['COLUMN_TYPE'], FILTER_SANITIZE_NUMBER_INT) < 60) {
        $pdo->exec("ALTER TABLE `u_miembros` MODIFY `user_password` VARCHAR(255) NOT NULL DEFAULT ''");
        $log[] = "[OK] user_password ampliada a VARCHAR(255).";
    } else {
        $log[] = "[SKIP] user_password ya admite hashes largos.";
    }

    $stats = upgrade_password_stats($pdo);
    $log[] = "[INFO] {$stats['modern']} cuentas ya usan user_password_hash.";
    $log[] = "[INFO] {$stats['legacy']} cuentas pendientes: se migran solas al primer login (sin pedir reseteo).";
    return $log;
}

/** 4. IPs VARCHAR -> VARBINARY(16) sin perder datos. */
function upgrade_run_ips(PDO $pdo, string $db): array
{
    $log = [];
    foreach (UPGRADE_IP_COLUMNS as [$table, $col]) {
        if (!upgrade_table_exists($pdo, $table)) {
            $log[] = "[SKIP] {$table}.{$col}: sin tabla.";
            continue;
        }
        $info = upgrade_column_info($pdo, $db, $table, $col);
        if ($info === null) {
            $log[] = "[SKIP] {$table}.{$col}: no existe columna.";
            continue;
        }
        if (strtolower($info['DATA_TYPE']) === 'varbinary') {
            $log[] = "[SKIP] {$table}.{$col} ya es VARBINARY.";
            continue;
        }
        $tmp = $col . '_bin_new';
        $tmpInfo = upgrade_column_info($pdo, $db, $table, $tmp);
        if ($tmpInfo === null) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$tmp}` VARBINARY(16) NULL DEFAULT NULL");
        }
        // Convierte IPv4 e IPv6; inválidas quedan NULL (no se pierde la fila)
        $n = $pdo->exec(
            "UPDATE `{$table}` SET `{$tmp}` = INET6_ATON(TRIM(`{$col}`))
             WHERE `{$col}` IS NOT NULL AND TRIM(`{$col}`) <> '' AND `{$tmp}` IS NULL"
        );
        $pdo->exec("ALTER TABLE `{$table}` DROP COLUMN `{$col}`");
        $pdo->exec("ALTER TABLE `{$table}` CHANGE COLUMN `{$tmp}` `{$col}` VARBINARY(16) NULL DEFAULT NULL");
        $log[] = "[OK] {$table}.{$col} migrada a VARBINARY(16) ({$n} IPs convertidas).";
    }
    return $log;
}

/** 4b. Estados *_status: inversión 0 <-> 1 sin tocar los demás valores. */

/**
 * Distribución de valores de una columna de estado: [valor => filas].
 * Devuelve [] si la tabla/columna no existe o no se puede leer.
 */
function upgrade_status_counts(PDO $pdo, string $table, string $col): array
{
    try {
        $st = $pdo->query("SELECT `{$col}` AS v, COUNT(*) AS n FROM `{$table}` GROUP BY `{$col}` ORDER BY v");
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[(string) $r['v']] = (int) $r['n'];
        }
        return $out;
    } catch (Throwable) {
        return [];
    }
}

/** "0 → 12, 1 → 340, 2 → 3" (para logs y pantalla de revisión). */
function upgrade_status_summary(array $counts): string
{
    if ($counts === []) {
        return 'sin datos';
    }
    $parts = [];
    foreach ($counts as $v => $n) {
        $parts[] = "{$v} → {$n}";
    }
    return implode(', ', $parts);
}

/** Ruta del marcador de inversión (uno por base de datos). */
function upgrade_statuses_marker_path(string $db): string
{
    return ABSPATH . '/storage/upgrade/statuses_' . preg_replace('/[^A-Za-z0-9_]/', '_', $db) . '.json';
}

/** Marcador guardado tras una inversión, o null si nunca se aplicó. */
function upgrade_statuses_marker(string $db): ?array
{
    $file = upgrade_statuses_marker_path($db);
    if (!is_file($file)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

/**
 * Guarda el marcador (auxiliar: si fallan permisos no aborta la migración,
 * en esa base la pantalla de revisión avisará que no hay registro previo).
 */
function upgrade_statuses_save_marker(string $db, array $columns): void
{
    $file = upgrade_statuses_marker_path($db);
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        return;
    }
    @file_put_contents($file, json_encode([
        'db'         => $db,
        'applied_at' => date('c'),
        'columns'    => $columns,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Ejecuta la inversión 0 <-> 1 en UPGRADE_STATUS_COLUMNS.
 *
 * Un solo UPDATE por tabla con CASE: 0 pasa a 1 y 1 a 0 en la misma sentencia,
 * así una fila no se puede invertir dos veces. $force repite la inversión aunque
 * exista el marcador (por ejemplo, tras restaurar un backup anterior).
 */
function upgrade_run_statuses(PDO $pdo, string $db, bool $force = false): array
{
    $log = [];
    $marker = upgrade_statuses_marker($db);
    if ($marker !== null && !$force) {
        $when = $marker['applied_at'] ?? '(sin fecha)';
        $log[] = "[SKIP] Estados *_status ya invertidos el {$when}: no se repite."
            . ' Si restauraste un backup anterior, marca “forzar re-invertir” en Revisión.';
        return $log;
    }
    if ($marker !== null && $force) {
        $log[] = '[INFO] Inversión forzada: se vuelve a aplicar 0 <-> 1 aunque ya existía registro previo.';
    }

    $changed = 0;
    $record = [];
    foreach (UPGRADE_STATUS_COLUMNS as [$table, $col]) {
        if (!upgrade_table_exists($pdo, $table)) {
            $log[] = "[SKIP] {$table}.{$col}: sin tabla.";
            continue;
        }
        if (upgrade_column_info($pdo, $db, $table, $col) === null) {
            $log[] = "[SKIP] {$table}.{$col}: no existe columna.";
            continue;
        }
        $before = upgrade_status_counts($pdo, $table, $col);
        $n0 = (int) ($before['0'] ?? 0);
        $n1 = (int) ($before['1'] ?? 0);
        if ($n0 + $n1 === 0) {
            $log[] = "[SKIP] {$table}.{$col}: ninguna fila con 0 o 1 (" . upgrade_status_summary($before) . ').';
            continue;
        }
        $others = max(0, array_sum($before) - $n0 - $n1);
        try {
            // Atómico: 0 <-> 1 en un solo UPDATE; 2, 3 u otros quedan intactos.
            $n = (int) $pdo->exec(
                "UPDATE `{$table}` SET `{$col}` = CASE `{$col}` WHEN 0 THEN 1 WHEN 1 THEN 0 ELSE `{$col}` END"
                . " WHERE `{$col}` IN (0, 1)"
            );
        } catch (Throwable $e) {
            $log[] = "[ERROR] {$table}.{$col}: " . $e->getMessage();
            continue;
        }
        $after = upgrade_status_counts($pdo, $table, $col);
        $record["{$table}.{$col}"] = ['before' => $before, 'after' => $after];
        $changed += $n;
        $log[] = "[OK] {$table}.{$col}: {$n0} filas 0 → 1 y {$n1} filas 1 → 0"
            . " ({$others} con otro valor sin cambios) — {$n} filas modificadas. Después: " . upgrade_status_summary($after) . '.';
    }

    if ($record !== []) {
        upgrade_statuses_save_marker($db, $record);
        $log[] = "[OK] Inversión de estados completada ({$changed} filas). Registro guardado en storage/upgrade/.";
    }
    return $log;
}

/** 5. Seeds base sin sobrescribir (rangos/categorías/config/stats). */
/** Estado de los passwords: modernos (ya en user_password_hash) vs pendientes. */
function upgrade_password_stats(PDO $pdo): array
{
    $stats = ['total' => 0, 'legacy' => 0, 'modern' => 0];
    try {
        $stats['total'] = (int) $pdo->query('SELECT COUNT(*) FROM `u_miembros`')->fetchColumn();

        $hasHash = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'u_miembros' AND COLUMN_NAME = 'user_password_hash'"
        )->fetchColumn();

        if ($hasHash > 0) {
            // Esquema nuevo: "legacy" = cuentas aún sin migrar (user_password_hash vacío)
            $stats['modern'] = (int) $pdo->query(
                "SELECT COUNT(*) FROM `u_miembros` WHERE user_password_hash <> ''"
            )->fetchColumn();
            $stats['legacy'] = (int) $pdo->query(
                "SELECT COUNT(*) FROM `u_miembros` WHERE user_password_hash = '' AND user_password <> ''"
            )->fetchColumn();
        } else {
            // Esquema anterior: todo vive en user_password
            $stats['modern'] = (int) $pdo->query(
                "SELECT COUNT(*) FROM `u_miembros`
                 WHERE user_password LIKE '\$2y\$%' OR user_password LIKE '\$argon%' OR CHAR_LENGTH(user_password) >= 60"
            )->fetchColumn();
            $stats['legacy'] = max(0, $stats['total'] - $stats['modern']);
        }
    } catch (Throwable) {
    }
    return $stats;
}

/**
 * Backup SQL desde PHP (sin mysqldump): CREATE + INSERTs por tabla.
 * Devuelve ruta del archivo o lanza excepción -> el caller muestra modo manual.
 */
function upgrade_backup_php(PDO $pdo, string $db): string
{
    $dir = ABSPATH . '/storage/backups';
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        throw new Exception('No se pudo crear ' . $dir . ' (permisos). Haz el backup manual.');
    }
    $file = $dir . '/upgrade_' . $db . '_' . date('Ymd_His') . '.sql';
    $fh = fopen($file, 'w');
    if (!$fh) {
        throw new Exception('No se pudo escribir el backup. Hazlo manual desde phpMyAdmin.');
    }
    fwrite($fh, "-- PHPost backup {$db} " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\n\n");
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        $row = $pdo->query("SHOW CREATE TABLE `{$t}`")->fetch(PDO::FETCH_ASSOC);
        $create = $row['Create Table'] ?? '';
        fwrite($fh, "-- Tabla {$t}\nDROP TABLE IF EXISTS `{$t}`;\n{$create};\n\n");
        $stmt = $pdo->query("SELECT * FROM `{$t}`");
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cols = implode(',', array_map(fn($c) => "`{$c}`", array_keys($r)));
            $vals = implode(',', array_map(
                fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                array_values($r)
            ));
            fwrite($fh, "INSERT INTO `{$t}` ({$cols}) VALUES ({$vals});\n");
        }
        fwrite($fh, "\n");
    }
    fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);
    return $file;
}

function upgrade_run_seeds(PDO $pdo): array
{
    $log = [];
    $collections = [];
    require upgrade_schema_file();
    foreach ($collections as $name => $sql) {
        if (!str_starts_with(strtoupper(ltrim($sql)), 'INSERT')) {
            continue;
        }
        // INSERT IGNORE: si la PK ya existe no duplica ni borra
        $safe = preg_replace('/^\s*INSERT INTO/i', 'INSERT IGNORE INTO', $sql);
        try {
            $n = $pdo->exec($safe);
            $log[] = "[OK] Seed {$name} ({$n} filas nuevas, existentes intactas).";
        } catch (Throwable $e) {
            $log[] = "[ERROR] Seed {$name}: " . $e->getMessage();
            throw $e;
        }
    }
    return $log;
}

/** Detección para la pantalla de revisión (sin aplicar nada). */
function upgrade_detect(PDO $pdo, string $db): array
{
    $missingTables = [];
    foreach (upgrade_table_statements() as $table => $sql) {
        if (!upgrade_table_exists($pdo, $table)) {
            $missingTables[] = $table;
        }
    }
    $missingCols = [];
    foreach (upgrade_column_definitions() as [$t, $c, $d]) {
        if (upgrade_table_exists($pdo, $t) && upgrade_column_info($pdo, $db, $t, $c) === null) {
            $missingCols[] = "{$t}.{$c}";
        }
    }
    $missingIndexes = [];
    foreach (upgrade_indexes_plan($pdo, $db) as $item) {
        $missingIndexes[] = "{$item['table']}.{$item['name']} ({$item['type']}: {$item['cols']})";
    }
    $ipsToMigrate = [];
    foreach (UPGRADE_IP_COLUMNS as [$t, $c]) {
        $info = upgrade_table_exists($pdo, $t) ? upgrade_column_info($pdo, $db, $t, $c) : null;
        if ($info !== null && strtolower($info['DATA_TYPE']) !== 'varbinary') {
            $ipsToMigrate[] = "{$t}.{$c} ({$info['COLUMN_TYPE']})";
        }
    }
    $pw = upgrade_password_stats($pdo);
    $statuses = [];
    $statusMarker = upgrade_statuses_marker($db);
    if ($statusMarker !== null) {
        $when = $statusMarker['applied_at'] ?? '(sin fecha)';
        $statuses[] = "[YA APLICADO] Estados *_status invertidos el {$when}. No se repetirá salvo que marques forzar.";
    }
    foreach (UPGRADE_STATUS_COLUMNS as [$t, $c]) {
        if (!upgrade_table_exists($pdo, $t)) {
            $statuses[] = "[SKIP] {$t}.{$c}: sin tabla.";
            continue;
        }
        if (upgrade_column_info($pdo, $db, $t, $c) === null) {
            $statuses[] = "[SKIP] {$t}.{$c}: no existe columna.";
            continue;
        }
        $counts = upgrade_status_counts($pdo, $t, $c);
        $n0 = (int) ($counts['0'] ?? 0);
        $n1 = (int) ($counts['1'] ?? 0);
        $others = max(0, array_sum($counts) - $n0 - $n1);
        $statuses[] = "{$t}.{$c}: " . upgrade_status_summary($counts)
            . " — se invertirán 0 ↔ 1 ({$n0} + {$n1} filas); {$others} con 2, 3 u otro valor quedan igual.";
        if ($statusMarker === null && $n0 === 0 && $n1 > 0) {
            $statuses[] = "[AVISO] {$t}.{$c}: no hay ninguna fila con 0. Si esta base ya está en el esquema nuevo (1 = activo), invertirla dejaría todo oculto.";
        }
    }
    $caps = phpostSchemaCaps($pdo);
    $modoEsquema = ($caps['flavor'] ?? '?') . ' ' . ($caps['version'] !== '' ? $caps['version'] : 'desconocida')
        . ' — esquema ' . (!empty($caps['json_default']) ? 'nativo (JSON)' : 'compatible (VARCHAR)')
        . (!empty($caps['fulltext']) ? '' : ', sin FULLTEXT')
        . (!empty($caps['key767']) ? ', claves con prefijo 191' : '');

    return [
        'Servidor detectado' => [$modoEsquema],
        'Tablas faltantes (se crearán)' => $missingTables ?: ['— ninguna, todas existen —'],
        'Columnas faltantes (se agregarán)' => $missingCols ?: ['— ninguna —'],
        'Índices faltantes (se agregarán)' => $missingIndexes ?: ['— ningún índice faltante —'],
        'IPs por migrar a VARBINARY(16)' => $ipsToMigrate ?: ['— ninguna, ya binarias —'],
        'Estados *_status por invertir (0 ↔ 1)' => $statuses ?: ['— nada que invertir —'],
        'Cuentas pendientes de migrar a password_hash' => [
            "{$pw['legacy']} de {$pw['total']} ({$pw['modern']} ya usan user_password_hash)",
        ],
    ];
}
