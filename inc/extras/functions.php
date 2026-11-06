<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

defined('TS_HEADER') OR exit('No se permite el acceso directo al script');

require_once TS_UTILS . '/Database/Database.php';
$dbConfig = require_once TS_ROOT . '/config.inc.php';

/**
 * Comprobamos que el archivo exista
*/
if($dbConfig["hostname"] === 'dbhost') {
    header("Location: ./install/index.php?step=welcome");
}

//
if (!isset($tsUser) || $tsUser === null) {
    $tsUser = new stdClass();
    $tsUser->is_admod = false;
    $tsUser->is_member = false;
}

$userLevel = match(true) {
    (APP_MODE === 'development') => 'desarrollador',
    (isset($tsUser->is_admod) && $tsUser->is_admod === true) => 'administrador',
    default => 'visitante'
};

/**
 * Ejecutar consulta
 */
function db_exec() {
    global $dbConfig, $config, $userLevel;

    static $db_instance = null;

    if ($db_instance === null) {
        $db_instance = new Database($dbConfig);
    }

    // Extraemos los parametros
    $args = func_get_args();
    $info  = $args[0] ?? null;
    // El tipo: query, fetch_assoc, etc...
    $type  = $args[1] ?? null;
    // Consultas a realiazr
    $query = $args[2] ?? null;
    // Para las sentencias preparadas
    $params = $args[3] ?? [];
    //
    if(is_array($info)) {
        $info = [
            'file' => $info[0] ?? '',
            'line' => $info[1] ?? 0,
            'query' => $query
        ];
    } else {
        // Hacemos cambios!
        $query = $type;
        $type = $info;
        if($type === 'query') {
            $info = [];
            $info['query'] = $query;
        }
    }

    try {
        // Si hay parámetros, usamos los métodos preparados de la clase Database
        if (!empty($params) && is_array($params)) {
            $orderedParams = [];

            // Expresión regular para buscar marcadores como :uid, :name, etc.
            $query = preg_replace_callback('/:[a-zA-Z0-9_]+/', function($matches) use ($params, &$orderedParams) {
                $key = ltrim($matches[0], ':');
                if (!array_key_exists($key, $params)) {
                    throw new InvalidArgumentException("Falta el parámetro requerido para el marcador de posición: :$key");
                }
                $orderedParams[] = $params[$key];
                return '?';
            }, (string)$query);

            $result = match ($type) {
                'query',
                'fetch_assoc',
                'fetch_array',
                'fetch_row' => $db_instance->getRow((string)$query, $orderedParams),
                'fetch_all' => $db_instance->getAll((string)$query, $orderedParams),
                'execute',
                'insert_id' => $db_instance->execute((string)$query, $orderedParams, ($type === 'insert_id')),
                default => throw new InvalidArgumentException(
                    "Operación '$type' con sentencias preparadas no soportada directamente."
                )
            };
        } else {
            $result = match ($type) {
                'query'              => $db_instance->query((string)$query),
                'real_escape_string' => $db_instance->escape((string)$query),
                'num_rows'           => $db_instance->numRows($query),
                'fetch_assoc'        => $db_instance->fetchAssoc($query),
                'fetch_array'        => $db_instance->fetchArray($query),
                'fetch_row'          => $db_instance->fetchRow($query),
                'insert_id'          => $db_instance->insertId(),
                'begin_transaction'  => $db_instance->beginTransaction(),
                'commit'             => $db_instance->commit(),
                'rollback'           => $db_instance->rollback(),
                'error'              => $db_instance->error(),
                'free_result'        => $db_instance->freeResult($query),
                'multi_query'        => $db_instance->multiQuery((string)$query),
                default              => throw new InvalidArgumentException("Operación '$type' no soportada.")
            };
            // Si la consulta falló de forma nativa en MySQLi (y no es una lectura de error explícitamente)
            if ($type === 'query' && $result === false) {
                throw new RuntimeException($db_instance->error());
            }
        }
        return $result;
    } catch (Throwable $e) {
        // Si la configuración global de visualización está apagada ('ninguno'), matamos el script en silencio
        if (($config['mostrar'] ?? 'visitantes') === 'ninguno') {
            exit();
        }
        // Construcción de la respuesta adaptada según el nivel determinado
        #var_dump($e->getMessage());
        return match ($userLevel) {
            'desarrollador' => show_error(
                "Error de Base de Datos: " . $e->getMessage(),
                'db',
                ['file' => $info['file'] ?? 'Desconocido', 'line' => $info['line'] ?? 0, 'query' => $query]
            ),
            'administrador' => show_error(
                "Ocurrió un inconveniente con una sentencia en el motor de base de datos.",
                'db',
                ['file' => basename($info['file'] ?? ''), 'line' => $info['line'] ?? 0, 'query' => 'Oculto por seguridad corporativa']
            ),
            default => show_error(
                "Hubo un problema temporal en el sistema de datos. Intente nuevamente más tarde.",
                'user_visible',
                []
            )
        };

    }
}

/**
 * Cargar resultados de forma moderna y eficiente
 */
function result_array(mixed $result): array {
    global $dbConfig;
    $db_instance = new Database($dbConfig);
    // Validar estrictamente si es un objeto de resultado válido de MySQLi
    if (!$result instanceof mysqli_result) {
        return [];
    }
    // Extraemos todos los registros de golpe en un arreglo asociativo nativo
    $array = $result->fetch_all(MYSQLI_ASSOC);
    // Liberamos la memoria del resultado inmediatamente
    $result->free();
    return $array;
}

/**
 * Mostrar error con diseño comprimido y agradable en pantalla
 */
function show_error($error = 'Indefinido', $type = 'db', $info = [])
{
    global $dbConfig, $tsUser, $userLevel;
    $db_instance = new Database($dbConfig);

    $table = '';

    if (!empty($info)) {
        $extra['file'] = isset($info['file']) ? '<tr><th>Archivo</th><td>'.$info['file'].'</td></tr>' : '';
        $extra['line'] = isset($info['line']) ? '<tr><th>Línea</th><td>'.$info['line'].'</td></tr>' : '';

        // El bloque query solo se expone al desarrollador
        $extra['query'] = (isset($info['query']) && $userLevel === 'desarrollador')
            ? '<tr><td colspan="2"><span>Código</span><pre><code>'.htmlspecialchars($info['query']).'</code></pre></td></tr>'
            : '';

        // El bloque del error interno de MySQL solo se expone al desarrollador
        $mysqlError = $db_instance ? $db_instance->error() : 'No se pudo recuperar el detalle del motor de datos';
        $extra['error'] = ($type === 'db' && !empty($mysqlError) && $userLevel === 'desarrollador')
            ? '<tr><th>MySQL Error</th><td><span class="mysql-raw">'.htmlspecialchars($mysqlError).'</span></td></tr>'
            : '';

        $table = '<table><tbody>' . $extra['file'] . $extra['line'] . $extra['query'] . $extra['error'] . '</tbody></table>';
    }

    require_once dirname(__DIR__, 1) . "/utils/database/Show_Error_Template.php";
}

// Borramos la variable por seguridad
unset($db);