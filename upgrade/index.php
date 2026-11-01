<?php

declare(strict_types=1);

/**
 * @package   PHPost_Risus_LTS
 * @subpackage Upgrade
 * Actualizador: misma estética/flujo AJAX que /install.
 * No borra datos: CREATE IF NOT EXISTS + ADD COLUMN si falta +
 * migración de IPs con INET6_ATON + passwords con re-hash en login.
 */

defined('TS_HEADER') OR define('TS_HEADER', TRUE);

define('ABSPATH',    dirname(__DIR__, 1));
define('FS_CONFIG',  ABSPATH . '/config.inc.php');
define('UPGRADE_DIR', __DIR__);

require_once ABSPATH . '/bootstrap.php';
require_once __DIR__ . '/migrations.php';

$app = [
    'title'   => 'PHPost Risus LTS',
    'version' => ['normal' => 'PHPost Remaster v1', 'code' => 'phpost_remaster_v1'],
    'others'  => [
        'original'   => 'https://github.com/isidromlc/PHPost',
        'subversion' => 'https://github.com/DevBroken/PHPost',
        'discord'    => 'https://discord.gg/StWZtrt2DE',
        'email'      => 'portfoliomiguel92@live.com',
    ],
];

$messages = [];
$continue = true;
$step = $_GET['step'] ?? 'welcome';
$whitelist = [
    'welcome'  => 'Bienvenida',
    'database' => 'Base de datos',
    'review'   => 'Revisión',
    'updating' => 'Actualización',
    'finish'   => 'Finalizar',
];
$started = 0;
$total = count($whitelist);

if (!in_array($step, array_keys($whitelist), true)) {
    die('Este paso <strong>' . htmlspecialchars($step) . '</strong> no es válido.');
}

function upSiteUrl(): string
{
    $ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 0) == 443) ? 's' : '';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = dirname($_SERVER['SCRIPT_NAME']);
    $dir = str_ends_with($dir, '/upgrade') ? substr($dir, 0, -strlen('/upgrade')) : $dir;
    return "http{$ssl}://{$host}" . rtrim($dir, '/\\');
}

function upConnect(array $db, mixed &$msg): ?PDO
{
    static $c = null;
    if ($c === null) {
        try {
            $c = new PDO(
                "mysql:host={$db['hostname']};dbname={$db['database']};charset=utf8mb4",
                $db['username'],
                $db['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
            );
        } catch (PDOException $e) {
            $msg = $e->getMessage();
        }
    }
    return $c;
}

function upIsPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function upRedirect(string $step): void
{
    if (upIsPost()) {
        header("Location: ./index.php?step={$step}");
        exit;
    }
}

function upRequireDb(): void
{
    if (!isset($_SESSION['UP']['DATABASE'])) {
        header('Location: ./index.php?step=database&action=retry');
        exit;
    }
}

/** Lee config.inc.php nuevo (return [...]) sin romper si tiene placeholders. */
function upLoadConfig(): array
{
    if (!is_file(FS_CONFIG)) {
        return [];
    }
    $data = include FS_CONFIG;
    if (!is_array($data)) {
        return [];
    }
    if (($data['hostname'] ?? '') === '__hostname__') {
        return [];
    }
    return $data;
}

switch ($step) {
    case 'welcome':
        $started = 1;
        if (upIsPost()) {
            $_SESSION['UP']['WELCOME'] = true;
            upRedirect('database');
        }
        break;

    case 'database':
        if (!isset($_SESSION['UP']['WELCOME'])) {
            header('Location: ./index.php?step=welcome&action=retry');
            exit;
        }
        $started = 2;
        $continue = false;
        $file = upLoadConfig();
        $saved = $_SESSION['UP']['DATABASE'] ?? $file;
        $default = [
            'hostname' => trim($_POST['hostname'] ?? $saved['hostname'] ?? ''),
            'username' => trim($_POST['username'] ?? $saved['username'] ?? ''),
            'password' => $_POST['password'] ?? $saved['password'] ?? '',
            'database' => trim($_POST['database'] ?? $saved['database'] ?? ''),
        ];
        if (upIsPost()) {
            if (empty($default['hostname']) || empty($default['username']) || empty($default['database'])) {
                $messages = 'Completa servidor, usuario y base de datos.';
            } else {
                $tmp = '';
                $pdo = upConnect($default, $tmp);
                if (!$pdo) {
                    $messages = 'Conexión fallida: ' . $tmp;
                } else {
                    // Debe existir instalación previa
                    try {
                        $pdo->query('SELECT 1 FROM `w_configuracion` LIMIT 1');
                    } catch (Throwable) {
                        $messages = 'No hay instalación previa (falta w_configuracion). Usa /install en su lugar.';
                        break;
                    }
                    $_SESSION['UP']['DATABASE'] = $default;
                    $_SESSION['UP']['DBNAME'] = $default['database'];
                    upRedirect('review');
                    $continue = true;
                }
            }
        }
        break;

    case 'review':
        upRequireDb();
        // Backup por AJAX (desde PHP, sin mysqldump)
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'backup') {
            header('Content-Type: application/json; charset=utf-8');
            $tmp = '';
            try {
                $pdo = upConnect($_SESSION['UP']['DATABASE'], $tmp);
                if (!$pdo) {
                    throw new Exception('Sin conexión: ' . $tmp);
                }
                $file = upgrade_backup_php($pdo, $_SESSION['UP']['DBNAME']);
                $_SESSION['UP']['BACKUP'] = $file;
                echo json_encode([
                    'ok' => true,
                    'msg' => '[OK] Backup guardado: ' . basename($file),
                    'file' => basename($file),
                ]);
                exit;
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode([
                    'ok' => false,
                    'msg' => '[ERROR] Backup automático imposible: ' . $e->getMessage()
                        . ' Hazlo manual: phpMyAdmin → Exportar, o mysqldump -u USER -p DB > backup.sql',
                ]);
                exit;
            }
        }
        $started = 3;
        $tmp = '';
        $pdo = upConnect($_SESSION['UP']['DATABASE'], $tmp);
        $detect = upgrade_detect($pdo, $_SESSION['UP']['DBNAME']);
        $statusMarker = upgrade_statuses_marker($_SESSION['UP']['DBNAME']);
        $pwStats = upgrade_password_stats($pdo);
        $hasBackup = !empty($_SESSION['UP']['BACKUP']) && is_file($_SESSION['UP']['BACKUP']);
        if (upIsPost()) {
            if (!$hasBackup && !isset($_POST['skip_backup'])) {
                $messages = 'Crea el backup antes de aplicar (o marca “lo haré manual”).';
                break;
            }
            $_SESSION['UP']['REVIEW'] = true;
            $_SESSION['UP']['FORCE_STATUSES'] = isset($_POST['force_statuses']);
            $_SESSION['UPGRADE_LOG'] = [];
            upRedirect('updating');
        }
        break;

    case 'updating':
        // Endpoint AJAX: una tarea por request, log en vivo
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            $job = $_GET['ajax'];
            $name = $_GET['name'] ?? '';
            if (!isset($_SESSION['UP']['DATABASE'])) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'msg' => '[ERROR] Sesión expirada.']);
                exit;
            }
            $tmp = '';
            try {
                $pdo = upConnect($_SESSION['UP']['DATABASE'], $tmp);
                if (!$pdo) {
                    throw new Exception('Sin conexión: ' . $tmp);
                }
                $db = $_SESSION['UP']['DBNAME'];
                if ($job === 'table') {
                    $all = upgrade_table_statements();
                    if (!isset($all[$name])) {
                        throw new Exception("Tabla desconocida: {$name}");
                    }
                    if (upgrade_table_exists($pdo, $name)) {
                        $msg = "[SKIP] Tabla {$name} ya existe, se conserva.";
                    } else {
                        // Adaptado a la versión real del servidor (JSON/índices)
                        $pdo->exec(phpostSchemaBuild($all[$name], phpostSchemaCaps($pdo)));
                        $msg = "[OK] Se creó tabla {$name}.";
                    }
                    $_SESSION['UPGRADE_LOG'][] = $msg;
                    echo json_encode(['ok' => true, 'msg' => $msg]);
                    exit;
                }
                if ($job === 'indexes') {
                    $lines = upgrade_run_indexes($pdo, $db);
                    foreach ($lines as $l) {
                        $_SESSION['UPGRADE_LOG'][] = $l;
                    }
                    echo json_encode(['ok' => true, 'msg' => implode("\n", $lines)]);
                    exit;
                }
                if ($job === 'columns') {
                    $lines = upgrade_run_columns($pdo, $db);
                    foreach ($lines as $l) {
                        $_SESSION['UPGRADE_LOG'][] = $l;
                    }
                    echo json_encode(['ok' => true, 'msg' => implode("\n", $lines)]);
                    exit;
                }
                if ($job === 'passwords') {
                    $lines = upgrade_run_passwords($pdo, $db);
                    foreach ($lines as $l) {
                        $_SESSION['UPGRADE_LOG'][] = $l;
                    }
                    echo json_encode(['ok' => true, 'msg' => implode("\n", $lines)]);
                    exit;
                }
                if ($job === 'ips') {
                    $lines = upgrade_run_ips($pdo, $db);
                    foreach ($lines as $l) {
                        $_SESSION['UPGRADE_LOG'][] = $l;
                    }
                    echo json_encode(['ok' => true, 'msg' => implode("\n", $lines)]);
                    exit;
                }
                if ($job === 'statuses') {
                    $lines = upgrade_run_statuses($pdo, $db, ($_GET['force'] ?? '') === '1');
                    foreach ($lines as $l) {
                        $_SESSION['UPGRADE_LOG'][] = $l;
                    }
                    echo json_encode(['ok' => true, 'msg' => implode("\n", $lines)]);
                    exit;
                }
                if ($job === 'seeds') {
                    $lines = upgrade_run_seeds($pdo);
                    foreach ($lines as $l) {
                        $_SESSION['UPGRADE_LOG'][] = $l;
                    }
                    echo json_encode(['ok' => true, 'msg' => implode("\n", $lines)]);
                    exit;
                }
                if ($job === 'done') {
                    $_SESSION['UP']['UPDATED'] = true;
                    $_SESSION['UPGRADE_LOG'][] = '[OK] Actualización completada sin pérdida de datos.';
                    echo json_encode(['ok' => true, 'msg' => '[OK] Actualización completada sin pérdida de datos.']);
                    exit;
                }
                http_response_code(400);
                echo json_encode(['ok' => false, 'msg' => '[ERROR] Tarea desconocida.']);
                exit;
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['ok' => false, 'msg' => '[ERROR] ' . $e->getMessage()]);
                exit;
            }
        }
        upRequireDb();
        if (!isset($_SESSION['UP']['REVIEW'])) {
            header('Location: ./index.php?step=review&action=retry');
            exit;
        }
        $started = 4;
        $allTables = array_keys(upgrade_table_statements());
        $forceStatuses = !empty($_SESSION['UP']['FORCE_STATUSES']);
        break;

    case 'finish':
        if (!isset($_SESSION['UP']['UPDATED'])) {
            header('Location: ./index.php?step=updating&action=retry');
            exit;
        }
        $started = 5;
        $upgrade_log = $_SESSION['UPGRADE_LOG'] ?? [];
        $tmp = '';
        $pdo = upConnect($_SESSION['UP']['DATABASE'], $tmp);
        $pwStats = $pdo ? upgrade_password_stats($pdo) : ['legacy' => 0, 'modern' => 0, 'total' => 0];
        break;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="robots" content="noindex, nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="<?= upSiteUrl() ?>/upgrade/assets/images/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&display=swap">
<link rel="stylesheet" href="<?= upSiteUrl() ?>/upgrade/assets/css/style.css">
<title>Actualizador &rsaquo; <?= htmlspecialchars($app['title']) ?></title>
</head>
<body>
<main>
    <header>
        <h1><?= htmlspecialchars($app['title']) ?> · Actualizador</h1>
        <span>Pasos <?= $started ?> de <?= $total ?></span>
    </header>
    <section>
        <aside>
            <ul>
                <?php foreach (array_values($whitelist) as $i => $label): ?>
                    <li class="item<?= ($i < ($started - 1)) ? ' ok' : '' ?><?= ($i === ($started - 1)) ? ' active' : '' ?>"><?= htmlspecialchars($label) ?></li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <form class="form-container" method="POST">
            <?php if ($messages): ?>
                <div class="alert"><span class="alert-desc"><?= htmlspecialchars(is_array($messages) ? implode(' ', $messages) : (string) $messages) ?></span></div>
            <?php endif; ?>
            <fieldset>
                <?php if ($started === 1 && $step === 'welcome'): ?>
                    <legend>Antes de actualizar</legend>
                    <div class="alert"><span class="alert-desc">El actualizador no borra posts, usuarios ni fotos: solo agrega tablas/columnas, migra IPs e invierte los estados <code>*_status</code> (0 ↔ 1) para dejar 1 = activo/publicado. Aun así, trabaja siempre con backup.</span></div>
                    <div class="form-buttons"><button type="submit" class="btn">Entendido, continuar</button></div>

                <?php elseif ($started === 2 && $step === 'database'): ?>
                    <legend>Conexión existente</legend>
                    <p>Se precarga desde <code>config.inc.php</code> si existe. Debe apuntar a tu instalación actual.</p>
                    <div class="form-group"><label>Servidor</label><input type="text" name="hostname" value="<?= htmlspecialchars($default['hostname'] ?? '') ?>" placeholder="localhost" required></div>
                    <div class="form-group"><label>Usuario</label><input type="text" name="username" value="<?= htmlspecialchars($default['username'] ?? '') ?>" placeholder="root" required></div>
                    <div class="form-group"><label>Contraseña</label><input type="password" name="password" value="<?= htmlspecialchars($default['password'] ?? '') ?>" placeholder="••••••••" autocomplete="current-password"></div>
                    <div class="form-group"><label>Base de datos</label><input type="text" name="database" value="<?= htmlspecialchars($default['database'] ?? '') ?>" placeholder="phpost_db" required></div>
                    <div class="form-buttons"><button type="submit" class="btn">Verificar y continuar</button></div>

                <?php elseif ($started === 3 && $step === 'review'): ?>
                    <legend>Qué se va a actualizar</legend>
                    <p>Detección en seco, sin aplicar cambios. Nada se borra.</p>

                    <h2 style="margin-bottom:0;">1 · Backup (obligatorio)</h2>
                    <div id="backup-box">
                        <?php if (!empty($hasBackup)): ?>
                            <div class="alert"><span class="alert-desc">Backup listo: <strong><?= htmlspecialchars(basename($_SESSION['UP']['BACKUP'])) ?></strong></span></div>
                        <?php else: ?>
                            <p>Intenta crearlo desde aquí. Si falla (permisos, hosting sin exec), hazlo manual: phpMyAdmin → Exportar, o <code>mysqldump -u USER -p DB &gt; backup.sql</code>.</p>
                        <?php endif; ?>
                        <div class="form-group"><label for="backup_log">Backup</label><textarea id="backup_log" rows="3" readonly style="font-family:monospace;"></textarea></div>
                        <div class="form-buttons">
                            <button type="button" id="btn-backup" class="btn ghost">Crear backup ahora</button>
                        </div>
                    </div>
                    <script>
                    (function(){
                        var b = document.getElementById('btn-backup'), l = document.getElementById('backup_log');
                        if(!b) return;
                        b.addEventListener('click', async function(){
                            b.disabled = true; b.textContent = 'Creando backup...';
                            try {
                                var r = await fetch('./index.php?step=review&ajax=backup', {headers:{'X-Requested-With':'XMLHttpRequest'}});
                                var j = await r.json();
                                l.value = j.msg;
                                if(j.ok){ b.textContent = 'Backup listo ✓'; }
                                else { b.disabled = false; b.textContent = 'Reintentar backup'; }
                            } catch(e){ l.value = '[ERROR] ' + e; b.disabled = false; b.textContent = 'Reintentar backup'; }
                        });
                    })();
                    </script>

                    <h2 style="margin-bottom:0;">2 · Passwords (solo si aplica)</h2>
                    <?php if (($pwStats['legacy'] ?? 0) > 0): ?>
                        <div class="alert"><span class="alert-desc"><strong><?= (int) $pwStats['legacy'] ?> cuentas pendientes de migrar.</strong> Se creará la columna <code>u_miembros.user_password_hash</code> (vigente, <code>password_hash()</code>); <code>user_password</code> conserva el md5 legacy tal cual. Cada cuenta se migrará sola en su primer login, sin pedir reseteo.</span></div>
                    <?php else: ?>
                        <p>Todas las cuentas ya usan <code>user_password_hash</code> (<?= (int) ($pwStats['modern'] ?? 0) ?> modernas de <?= (int) ($pwStats['total'] ?? 0) ?>). Sin acción en passwords.</p>
                    <?php endif; ?>

                    <h2 style="margin-bottom:0;">3 · Estados de contenido (*_status)</h2>
                    <div class="alert"><span class="alert-desc">Se invierten <strong>0 ↔ 1</strong> en <code>p_posts.post_status</code>, <code>p_comentarios.c_status</code>, <code>f_fotos.f_status</code> y <code>p_borradores.b_status</code>: <strong>1 = activo/publicado</strong>, <strong>0 = oculto/desactivado</strong>. Los valores <strong>2, 3 u otros</strong> no se tocan. Se aplica tras el backup y con un único UPDATE por tabla (ninguna fila se invierte dos veces).</span></div>
                    <?php if (!empty($statusMarker)): ?>
                        <div class="alert"><span class="alert-desc">Esta base ya registró una inversión el <strong><?= htmlspecialchars($statusMarker['applied_at'] ?? '(sin fecha)') ?></strong>: este paso se omitirá salvo que marques la casilla de abajo (útil solo si restauraste un backup anterior).</span></div>
                    <?php endif; ?>
                    <div class="form-group" style="flex-direction:row;align-items:center;gap:8px;">
                        <input type="checkbox" id="force_statuses" name="force_statuses" value="1" style="width:auto;" <?= !empty($_SESSION['UP']['FORCE_STATUSES']) ? 'checked' : '' ?>>
                        <label for="force_statuses" style="font-weight:400;">Forzar re-invertir estados (solo si ya se aplicó antes o restauraste un backup viejo).</label>
                    </div>

                    <h2 style="margin-bottom:0;">4 · Cambios detectados</h2>
                    <?php foreach ($detect ?? [] as $h => $items): ?>
                        <h2 style="margin-bottom:0;font-size:0.9rem;"><?= htmlspecialchars($h) ?></h2>
                        <ul><?php foreach ($items as $v): ?><li><span><?= htmlspecialchars((string) $v) ?></span></li><?php endforeach; ?></ul>
                    <?php endforeach; ?>
                    <div class="form-group" style="flex-direction:row;align-items:center;gap:8px;">
                        <input type="checkbox" id="skip_backup" name="skip_backup" value="1" style="width:auto;">
                        <label for="skip_backup" style="font-weight:400;">Ya hice el backup manual, aplicar sin backup automático.</label>
                    </div>
                    <div class="form-buttons"><button type="submit" class="btn">Aplicar actualización</button></div>

                <?php elseif ($started === 4 && $step === 'updating'): ?>
                    <legend>Actualizando en vivo...</legend>
                    <p>Progreso por AJAX, tabla por tabla. No cierres esta ventana.</p>
                    <div class="form-group" id="live-wrap">
                        <label for="upgrade_live">Progreso</label>
                        <div id="install-bar"><div id="install-bar-fill"></div></div>
                        <textarea id="upgrade_live" rows="14" readonly style="font-family:monospace;white-space:pre;width:100%;"></textarea>
                    </div>
                    <div class="form-buttons" id="upgrade-actions">
                        <button type="button" id="btn-upgrade" class="btn">Actualizar ahora</button>
                        <a id="btn-finish" class="btn" href="./index.php?step=finish" style="display:none;text-align:center;text-decoration:none;">Ver resumen</a>
                    </div>
                    <script>let upTables = <?= json_encode($allTables ?? []) ?>; let upForceStatuses = <?= !empty($forceStatuses) ? 'true' : 'false' ?>;</script>
                    <script src="<?= upSiteUrl() ?>/upgrade/assets/js/updating.js"></script>

                <?php elseif ($started === 5 && $step === 'finish'): ?>
                    <legend>¡Actualizado!</legend>
                    <p>Datos conservados. Borra la carpeta <strong>upgrade</strong> por seguridad.</p>
                    <?php if (!empty($upgrade_log)): ?>
                        <div class="form-group"><label>Registro final</label><textarea rows="10" readonly style="font-family:monospace;white-space:pre;"><?= htmlspecialchars(implode("\n", $upgrade_log)) ?></textarea></div>
                    <?php endif; ?>
                    <?php if (($pwStats['legacy'] ?? 0) > 0): ?>
                    <h2>Migración de contraseñas (automática)</h2>
                    <p>Quedan <strong><?= (int) $pwStats['legacy'] ?></strong> cuentas sin migrar. No hay que hacer nada: <code>/inc/class/c.user.php</code> verifica primero <code>user_password_hash</code>; si está vacío comprueba el md5 de <code>user_password</code> y, si la clave es correcta, guarda el <code>password_hash()</code> nuevo en <code>user_password_hash</code>. A partir de ahí solo se usa la columna nueva.</p>
                    <?php else: ?>
                    <p>Tus passwords ya están en <code>user_password_hash</code>. Nada que migrar aquí.</p>
                    <?php endif; ?>
                    <div class="form-buttons"><a class="btn" href="<?= htmlspecialchars(upSiteUrl()) ?>" style="text-align:center;text-decoration:none;">Ir al sitio</a></div>
                <?php endif; ?>
            </fieldset>
        </form>
    </section>
    <footer>
        <p>Copyright &copy; 2011-2026</p>
        <p>
            <a href="<?= $app['others']['original'] ?>" target="_blank" rel="noopener noreferrer" aria-label="Ir al repositorio original de PHPost en GitHub"><?= $app['title'] ?></a>
            <span> &middot; SubVersion </span> <a href="<?= $app['others']['subversion'] ?>" target="_blank" rel="noopener noreferrer" aria-label="Ir al repositorio de la SubVersion PHPostV3 en GitHub">PHPostV3</a>
        </p>
        <!-- Tu créditos de actualización y contacto -->
        <p class="author-credits">
            Actualizado por
            <a href="<?= str_replace('/PHPost', '', $app['others']['subversion']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Ver perfil de DevBroken en GitHub">DevBroken</a> &middot; <a href="<?= $app['others']['discord'] ?>" target="_blank" rel="noopener noreferrer" aria-label="Contactar al soporte en el servidor de Discord">Soporte y Contacto</a>
        </p>
    </footer>
</main>
</body>
</html>
