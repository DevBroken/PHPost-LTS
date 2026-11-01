<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

defined('TS_HEADER') OR define('TS_HEADER', TRUE);

define('ABSPATH',       dirname(__DIR__, 1));
// ARCHIVOS
define('FS_EXAMPLE',	__DIR__ . '/example.php');
define('FS_CONFIG',     ABSPATH . '/config.inc.php');
define('FS_LOCKFILE',   ABSPATH . '/install.lock');
// DIRECTORIOS
define('TS_AVATAR',     ABSPATH . '/storage/avatar/');
define('TS_UPLOADS',    ABSPATH . '/storage/uploads/');
define('TS_CACHE',      ABSPATH . '/storage/cache/');

require_once ABSPATH . '/bootstrap.php';

// Creamos variable para almacenar los datos!
$ACTIONFORM  = '';
$messages    = [];
$started     = 0;
$continue    = true;
$step        = $_GET['step'] ?? 'welcome';
$whitelist   = [
	'welcome'       => 'Bienvenida',
	'permissions'   => 'Permisos',
	'requirements'  => 'Requisitos',
	'database'      => 'Base de datos',
	'site'          => 'Datos del sitio',
	'administrator' => 'Datos administrador',
	'installing'    => 'Instalación',
	'finish'        => 'Finalizar'
];
$total       = count($whitelist);

if(!in_array($step, array_keys($whitelist), true)) {
	die('Este paso <strong>' . $step . '</strong> no es válido.');
}

function getSiteUrl(): string {
	$ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] === 443) ? 's' : '';
	$protocol = "http{$ssl}://";
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
	$installerFolder = '/install';
	if (str_ends_with($scriptDir, $installerFolder)) {
		$scriptDir = substr($scriptDir, 0, -strlen($installerFolder));
	}
	$scriptDir = rtrim($scriptDir, '/\\');
	return $protocol . $host . $scriptDir;
}

function crearSlug(string $texto, string $separator = '-'): string {
	$texto = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $texto);
	if ($texto === false) {
		return '';
	}
	$texto = preg_replace('/[^a-z0-9]+/i', $separator, $texto);
	return strtolower(trim($texto, $separator));
}

function conectar(array $db, mixed &$messages) {
	// La variable estática retiene su valor entre llamadas a la función
	static $conexion = null;
	if ($conexion === null) {
		$charset = 'utf8mb4';
		$dsn = "mysql:host={$db['hostname']};dbname={$db['database']};charset=$charset";
		$opciones = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		];
		try {
			$conexion = new PDO($dsn, $db['username'], $db['password'], $opciones);
		} catch (PDOException $e) {
			error_log("Error PDO: " . $e->getMessage());
			$messages = $e->getMessage();
		}
	}
	return $conexion;
}

function requestPost() {
	return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Si es action=bad destruye la session
if(isset($_GET['action']) && $_GET['action'] === 'bad') {
	session_destroy();
}

function stopThere() {
	if(!isset($_SESSION['license']) || $_SESSION['license'] !== true) {
		header("Location: ./index.php?step=welcome&action=bad");
		die();
	}
}

function goBack(string $key = 'welcome') {
	stopThere();
	$sessionKey = strtoupper($key);
	if(!isset($_SESSION['STATUS'][$sessionKey]) || $_SESSION['STATUS'][$sessionKey] !== true) {
		header("Location: ./index.php?step={$key}&action=retry");
		die();
	}
}

function redirectTO(string $step) {
	$url = "./index.php?step={$step}";
	if(requestPost()) {
		header("Location: {$url}");
		exit;
	}
}

if (is_file(FS_LOCKFILE)) {
    header("Location: ../");
	exit;
}

$app_name = 'PHPost Risus LTS';
$app_version = 'v1.0.0';
$app = [
	'title'  => $app_name,
	'version' => [
		'normal' => "{$app_name} {$app_version}",
		'code'   => crearSlug("{$app_name} {$app_version}", '_')
	],
	'others' => [
		'original'   => 'https://github.com/isidromlc/PHPost',
		'subversion' => 'https://github.com/DevBroken/PHPost',
		'discord'    => 'https://discord.gg/StWZtrt2DE',
		'email'      => 'portfoliomiguel92@gmail.com'
	],
	'prefix'     => 'LTS_', // Sin uso por el momento
	'asignature' => [
		'cert'   => 'a053758ef11670c88a1f',
		'key'    => '959b4adb0444617939a2',
		'public' => '5c5960c2ed194ae4bc4a',
		'script' => '928a950fb9f5d1c746fd'
	]
];

switch ($step) {

	// PASO 1 :: BIENVENIDA / LICENCIA
	case 'welcome':
		$started = 1;
		$license = file_get_contents(ABSPATH . '/LICENSE');
		if (requestPost() && isset($_POST['license'])) {
			$_SESSION['license'] = true;
			redirectTO('permissions');
		}
	break;

	// PASO 2 :: COMPROBACIÓN DE PERMISOS
	case 'permissions':
		if (requestPost() && isset($_POST['license'])) {
			$_SESSION['license'] = true;
		}

		$started = 2;
		$permisos = [];
		foreach ([FS_CONFIG => false, TS_AVATAR => true, TS_UPLOADS => true, TS_CACHE => true] as $dir => $bool) {
			$exists = $bool ? is_dir($dir) : file_exists($dir);
			$perm = $bool ? 0750 : 0644;
			if (!$exists) {
				$exists = $bool ? mkdir($dir, 0750, true) : copy(FS_EXAMPLE, FS_CONFIG);
				if ($exists) {
					chmod($dir, $perm);
				}
			}
			$permisos[basename($dir)] = [
				'name' => ucfirst(basename($dir)),
				'path' => str_replace(ABSPATH, '..', $dir),
				'css'  => $exists ? 'success' : 'danger',
				'text' => $exists ? 'OK' : 'NO'
			];
		}

		if(in_array('NO', array_column($permisos, 'text'), true)) {
			$continue = false;
		}
		// Si todo está OK y el usuario pulsó Continuar, avanzamos
		if(requestPost()) {
			if($continue) {
				$_SESSION['STATUS']['PERMISSIONS'] = true;
				redirectTO('requirements');
			} else {
				$messages = 'Corrige los permisos de escritura y vuelve a intentarlo.';
			}
		}

	break;

	// PASO 3 :: COMPROBACIÓN LOS REQUISITOS NECESARIOS
	case 'requirements':
		goBack('permissions');

		$started = 3;

		$version_minima = '8.4.27'; // Version segura
		$version_maxima = '8.5.12'; // Version segura

		// Obtener la versión actual de PHP instalada en el servidor
		$version_actual = PHP_VERSION;

		// Validar rango de versiones
		$version_ok = (version_compare($version_actual, $version_minima, '>=') && version_compare($version_actual, $version_maxima, '<='));

		// Lista de extensiones obligatorias a verificar
		$extensions = ['curl', 'pdo', 'pdo_mysql', 'mysqli', 'mbstring', 'json', 'gd', 'openssl', 'fileinfo', 'zip'];
		$status_extensions = [];
		$extensionUse = true;

		foreach ($extensions as $ext) {
		    $isLoaded = extension_loaded($ext);
			$status_extensions[$ext] = [
				'name' => ucfirst($ext),
				'css'  => $isLoaded ? 'success' : 'danger',
				'text' => $isLoaded ? 'OK' : 'NO'
			];
		    $extensionUse = $isLoaded;
		}
		$continuar = ($version_ok && $extensionUse);
		if(requestPost()) {
			if($continue) {
				$_SESSION['STATUS']['REQUIREMENTS'] = true;
				redirectTO('database');
			} else {
				$messages = 'Corrige los requisitos y vuelve a intentarlo.';
			}
		}

	break;

	// PASO 4 :: COMPROBACIÓN LA CONEXIÓN DE BASE DE DATOS
	case 'database':
		goBack('requirements');

		$started = 4;
		$continue = false;
		$saved = $_SESSION['FORM']['DATABASE'] ?? [];
		$default = [
			'hostname' => trim($_POST['hostname'] ?? $saved['hostname'] ?? ''),
			'username' => trim($_POST['username'] ?? $saved['username'] ?? ''),
			'password' => $_POST['password'] ?? $saved['password'] ?? '',
			'database' => trim($_POST['database'] ?? $saved['database'] ?? '')
		];

		if (requestPost()) {
			$isLocalFb = $isLocal ?? false;
			// Comprobando si esta vacio
			if(empty($default['hostname']) || empty($default['username']) || empty($default['database']) || (empty($default['password']) && !$isLocalFb)) {
				$messages = 'Todos los campos son requeridos.';
			} else {
				try {
					$conexion = conectar($default, $messages);
					if ($conexion) {
						// Guardamos los datos en el archivo!
						$template = file_get_contents(FS_EXAMPLE);
						$configContenido = str_replace(
							['__hostname__','__username__', '__password__', '__database__'],
							[$default['hostname'], $default['username'], $default['password'], $default['database']],
							$template
						);
						file_put_contents(FS_CONFIG, $configContenido);
						$_SESSION['FORM']['DATABASE'] = $default;
						// Versión del servidor: decide cómo se construye el esquema
						try {
							$_SESSION['FORM']['SERVER'] = (string) $conexion->query('SELECT VERSION()')->fetchColumn();
						} catch (Throwable) {
							$_SESSION['FORM']['SERVER'] = '';
						}
						$continue = true;
					} else {
						if(empty($messages)) {
							$messages = 'No se pudo conectar con esos datos.';
						}
					}
				} catch (Exception $e) {
					$messages = $e->getMessage();
				}
			}
			if($continue) {
				$_SESSION['STATUS']['DATABASE'] = true;
				redirectTO('site');
			}
		}
	break;

	// PASO 5 :: ALMACENAMOS LOS DATOS DEL SITIO
	case 'site':
		goBack('database');

		$started = 5;
		$continue = false;
		$savedSite = $_SESSION['FORM']['SITE'] ?? [];
		$default = [
			'titulo' => trim($_POST['titulo'] ?? $savedSite['titulo'] ?? ''),
			'slogan' => trim($_POST['slogan'] ?? $savedSite['slogan'] ?? ''),
			'url'    => trim($_POST['url'] ?? $savedSite['url'] ?? getSiteUrl()),
			'email'  => trim($_POST['email_site'] ?? $savedSite['email'] ?? ''),
			'pkey'   => trim($_POST['pkey'] ?? $savedSite['pkey'] ?? ''),
			'skey'   => trim($_POST['skey'] ?? $savedSite['skey'] ?? ''),
		];

		if (requestPost()) {
			$required = [$default['titulo'], $default['slogan'], $default['url'], $default['email']];
			if(in_array('', $required, true)) {
				$messages = 'Todos los campos obligatorios son requeridos.';
			} elseif(!filter_var($default['email'], FILTER_VALIDATE_EMAIL)) {
				$messages = 'Introduzca un email del sitio correcto.';
			} elseif(!filter_var($default['url'], FILTER_VALIDATE_URL)) {
				$messages = 'La URL del sitio no es válida.';
			} else {
				$continue = true;
			}
			if($continue) {
				$_SESSION['FORM']['SITE'] = $default;
				$_SESSION['STATUS']['SITE'] = true;
				redirectTO('administrator');
			}
		}
	break;

	// PASO 6 :: ALMACENAMOS LOS DATOS DEL ADMINISTRADOR
	case 'administrator':
		goBack('site');

		$started = 6;
		$continue = false;
		$savedAdmin = $_SESSION['FORM']['ADMINISTRATOR'] ?? [];
		$default = [
			'username'  => trim($_POST['username'] ?? $savedAdmin['username'] ?? ''),
			'password'  => $_POST['password'] ?? '',
			'confirm'   => $_POST['confirm'] ?? '',
			'email'     => trim($_POST['email'] ?? $savedAdmin['email'] ?? '')
		];

		if (requestPost()) {
			$nickLen = strlen($default['username']) < 4;
			if(in_array('', [$default['username'], $default['password'], $default['confirm'], $default['email']], true)) {
				$messages = 'Todos los campos son obligatorios.';
			} elseif (!ctype_alnum($default['username']) || $nickLen) {
				$messages = $nickLen ? 'El nickname debe tener al menos 4 caracteres.' : 'El nickname debe ser alfanumérico.';
			} elseif (!filter_var($default['email'], FILTER_VALIDATE_EMAIL)) {
				$messages = 'Introduzca un email correcto.';
			} elseif ($default['password'] !== $default['confirm']) {
				$messages = "Las contraseñas no coinciden.";
			} elseif (strlen($default['password']) < 8) {
				$messages = "La contraseña debe tener al menos 8 caracteres.";
			} elseif (!preg_match('/[A-Z]/', $default['password'])) {
				$messages = "La contraseña debe contener al menos una letra mayúscula.";
			} elseif (!preg_match('/[0-9]/', $default['password'])) {
				$messages = "La contraseña debe contener al menos un número.";
			} else {
				$continue = true;
			}
			if($continue) {
				$_SESSION['FORM']['ADMINISTRATOR'] = $default;
				$_SESSION['STATUS']['ADMINISTRATOR'] = true;
				redirectTO('installing');
			}
		}
	break;

	// PASO 7 :: REVISAR DATOS + EJECUTAR INSTALACIÓN (AJAX en vivo)
	case 'installing':
		// --- Endpoint AJAX: ejecuta una tarea por request y devuelve JSON ---
		if(isset($_GET['ajax'])) {
			header('Content-Type: application/json; charset=utf-8');
			$job = $_GET['ajax'];
			$name = $_GET['name'] ?? '';
			$dbData = $_SESSION['FORM']['DATABASE'] ?? null;
			$siteData = $_SESSION['FORM']['SITE'] ?? null;
			$adminData = $_SESSION['FORM']['ADMINISTRATOR'] ?? null;
			if(empty($dbData)) {
				http_response_code(403);
				echo json_encode(['ok' => false, 'msg' => '[ERROR] Sesión expirada, recarga el instalador.']);
				exit;
			}
			$tmp = '';
			try {
				$pdo = conectar($dbData, $tmp);
				if(!$pdo) {
					throw new Exception('Sin conexión: ' . (is_string($tmp) ? $tmp : 'error'));
				}
				$collections = [];
				require_once dirname(__DIR__, 1) . '/inc/utils/database/collections.php';
				require_once dirname(__DIR__, 1) . '/inc/utils/password/password.php';
				if($job === 'table') {
					if(!isset($collections[$name])) {
						throw new Exception("Tabla desconocida: {$name}");
					}
					// El esquema se adapta a la versión real del servidor
					$pdo->exec(phpostSchemaBuild($collections[$name], phpostSchemaCaps($pdo)));
					$_SESSION['INSTALL_LOG'][] = "[OK] Se creó tabla {$name}.";
					echo json_encode(['ok' => true, 'msg' => "[OK] Se creó tabla {$name}."]);
					exit;
				}
				if($job === 'insert') {
					if(!isset($collections[$name])) {
						throw new Exception("Insert desconocido: {$name}");
					}
					$pdo->exec(phpostSchemaBuild($collections[$name], phpostSchemaCaps($pdo)));
					$_SESSION['INSTALL_LOG'][] = "[OK] Se insertó datos en {$name}.";
					echo json_encode(['ok' => true, 'msg' => "[OK] Se insertó datos en {$name}."]);
					exit;
				}
				if($job === 'site') {
					$stmt = $pdo->prepare("UPDATE w_configuracion SET titulo = :titulo, slogan = :slogan, url = :url, email = :email, pkey = :pkey, skey = :skey, version = :version, version_code = :version_code WHERE tscript_id = 1");
					$stmt->execute([
						'titulo'       => $siteData['titulo'],
						'slogan'       => $siteData['slogan'],
						'url'          => rtrim($siteData['url'], '/'),
						'email'        => $siteData['email'],
						'pkey'         => $siteData['pkey'] ?? '',
						'skey'         => $siteData['skey'] ?? '',
						'version'      => $app['version']['normal'],
						'version_code' => $app['version']['code'],
					]);
					$_SESSION['INSTALL_LOG'][] = '[OK] Se actualizó la configuración del sitio.';
					echo json_encode(['ok' => true, 'msg' => '[OK] Se actualizó la configuración del sitio.']);
					exit;
				}
				if($job === 'admin') {
					$check = $pdo->query("SELECT user_id FROM u_miembros WHERE user_rango = 1 LIMIT 1")->fetch();
					if($check) {
						$_SESSION['INSTALL_LOG'][] = '[AVISO] Ya existe un administrador, se omite la creación.';
						echo json_encode(['ok' => true, 'msg' => '[AVISO] Ya existe un administrador, se omite la creación.']);
						exit;
					}
					// Hash moderno en la columna vigente (el login acepta legacy y migra solo)
					$hash = Password::hash($adminData['password']);
					$now = time();
					$stmt = $pdo->prepare("INSERT INTO u_miembros (user_name, user_password_hash, user_email, user_rango, user_registro, user_puntosxdar, user_activo) VALUES (:nick, :pass, :email, 1, :now, 50, 1)");
					$stmt->execute(['nick' => $adminData['username'], 'pass' => $hash, 'email' => $adminData['email'], 'now' => $now]);
					$uid = (int)$pdo->lastInsertId();
					// Explícito para compatibilidad con modo estricto y tablas ya creadas con el esquema viejo
					$stmtPerfil = $pdo->prepare("INSERT INTO u_perfil (user_id, user_firma, p_sitio, p_socials) VALUES (:uid, '', '', '')");
					$stmtPerfil->execute(['uid' => $uid]);
					$pdo->exec("INSERT INTO u_portal (user_id) VALUES ({$uid})");
					$pdo->exec("UPDATE w_stats SET stats_time_foundation = {$now}, stats_time_upgrade = {$now} WHERE stats_no = 1");
					$_SESSION['INSTALL_LOG'][] = "[OK] Se creó el administrador {$adminData['username']} (ID {$uid}).";
					echo json_encode(['ok' => true, 'msg' => "[OK] Se creó el administrador {$adminData['username']} (ID {$uid})."]);
					exit;
				}
				if($job === 'done') {
					$_SESSION['STATUS']['INSTALLING'] = true;
					$_SESSION['INSTALL_LOG'][] = '[OK] Instalación completada.';
					echo json_encode(['ok' => true, 'msg' => '[OK] Instalación completada.']);
					exit;
				}
				http_response_code(400);
				echo json_encode(['ok' => false, 'msg' => '[ERROR] Tarea desconocida.']);
				exit;
			} catch(Exception $e) {
				http_response_code(500);
				echo json_encode(['ok' => false, 'msg' => '[ERROR] ' . $e->getMessage()]);
				exit;
			}
		}

		goBack('administrator');

		$started = 7;
		$dbData = $_SESSION['FORM']['DATABASE'] ?? null;
		$siteData = $_SESSION['FORM']['SITE'] ?? null;
		$adminData = $_SESSION['FORM']['ADMINISTRATOR'] ?? null;

		if(empty($dbData) || empty($siteData) || empty($adminData)) {
			header("Location: ./index.php?step=database&action=retry");
			exit;
		}
		// Lista de tareas para el JS (tablas + inserts en orden)
		$collections = [];
		require_once dirname(__DIR__, 1) . '/inc/utils/Database/collections.php';
		$taskTables = [];
		$taskInserts = [];
		foreach($collections as $nombre => $sql) {
			if(str_starts_with(strtoupper(ltrim($sql)), 'INSERT')) {
				$taskInserts[] = $nombre;
			} else {
				$taskTables[] = $nombre;
			}
		}

		// Para la vista de revisión (sin password/confirm)
		$data = [
			'Base de datos' => [
				'hostname' => $dbData['hostname'] ?? '',
				'username' => $dbData['username'] ?? '',
				'database' => $dbData['database'] ?? '',
				'servidor' => $_SESSION['FORM']['SERVER'] ?? 'desconocido',
			],
			'Sitio' => [
				'titulo' => $siteData['titulo'] ?? '',
				'slogan' => $siteData['slogan'] ?? '',
				'url'    => $siteData['url'] ?? '',
				'email'  => $siteData['email'] ?? '',
			],
			'Administrador' => [
				'username' => $adminData['username'] ?? '',
				'email'    => $adminData['email'] ?? '',
			]
		];

		// Vista: ya no se instala por POST sync, todo va por AJAX en vivo.
		// Limpiamos log previo al entrar a revisar.
		if(!requestPost()) {
			$_SESSION['INSTALL_LOG'] = [];
		}
		$install_log = $_SESSION['INSTALL_LOG'] ?? [];
	break;

	// PASO 8 :: FINALIZAR
	case 'finish':
		if(!isset($_SESSION['STATUS']['INSTALLING']) || $_SESSION['STATUS']['INSTALLING'] !== true) {
			header("Location: ./index.php?step=installing&action=retry");
			exit;
		}
		$started 		= 8;
		$install_log 	= $_SESSION['INSTALL_LOG'] ?? [];
		$siteData 		= $_SESSION['FORM']['SITE'] ?? [];
		$adminData 		= $_SESSION['FORM']['ADMINISTRATOR'] ?? [];

		$contenido_lock = 'Fecha: ' . date('d-m-Y') . PHP_EOL .
		'Script: ' . ($app['title'] ?? 'PHPost Risus') . PHP_EOL .
		'Certificado: ' . $app['asignature']['cert'] . PHP_EOL .
		'Secreto: ' . $app['asignature']['key'] . PHP_EOL .
		'Publico: ' . $app['asignature']['public'] . PHP_EOL .
		'Token: ' . $app['asignature']['script'] . PHP_EOL;

		// Guardamos
		file_put_contents(FS_LOCKFILE, $contenido_lock);
		session_destroy();
	break;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="robots" content="noindex, nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="author" content="DevBroken">
<meta name="reply-to" content="<?= $app['others']['email'] ?>">
<link rel="author" href="<?= $app['others']['subversion'] ?>">
<link rel="icon" type="image/svg+xml" href="<?= getSiteUrl() ?>/install/assets/images/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&display=swap">
<link rel="stylesheet" href="<?= getSiteUrl() ?>/install/assets/css/style.css">
<title>Instalador &rsaquo; <?= htmlspecialchars($app['title']) ?></title>
</head>
<body>

	<main>
		<header>
			<h1><?= $app['title'] ?></h1>
			<span>Pasos <?= $started ?> de <?= $total ?></span>
		</header>
		<section>
			<aside>
				<div class="loader"></div>
				<ul>
					<?php foreach(array_values($whitelist) as $item => $label): ?>
						<li class="item<?= ($item < ($started - 1)) ? ' ok' : '' ?><?= ($item === ($started - 1)) ? ' active' : '' ?>"><?= $label ?></li>
					<?php endforeach; ?>
				</ul>
			</aside>

			<form class="form-container" action="<?= $ACTIONFORM ?>" method="POST">

				<?php if($messages): ?>
					<div class="alert">
						<?php if(is_array($messages)): foreach($messages as $k => $msg): ?>
							<div>
								<strong class="alert-title"><?= $k ?></strong>:
								<span class="alert-desc"><?= $msg ?></span>
							</div>
						<?php endforeach; else: ?>
							<span class="alert-desc"><?= $messages ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<fieldset>
					<?php if($started === 1 && $step === 'welcome'): ?>

						<!-- ACEPTAR LA LICENCIA Y CONTINUAR -->
						<legend>Licencia</legend>
						<div class="form-group">
							<label for="license">Para utilizar <strong><?= $app['title'] ?></strong> debes estar de acuerdo con nuestra licencia de uso.</label>
							<textarea id="license" rows="10"><?= $license ?? 'No se cargo la licencia...' ?></textarea>
							<input type="hidden" name="license" value="true">
						</div>

						<div class="form-buttons">
							<button type="submit" class="btn">Continuar</button>
						</div>

					<?php elseif($started === 2 && $step === 'permissions'): ?>

						<!-- PERMISOS DE ESCRITURA NECESARIA  -->
						<legend>Permisos de escritura</legend>
						<?php foreach($permisos ?? [] as $nombre => $permiso): ?>
							<div class="form-group">
								<div class="label"><?= strtoupper($nombre) ?></div>
								<div class="group">
									<span><?= $permiso['path'] ?></span>
									<div class="badge <?= $permiso['css'] ?>">
										<?= $permiso['text'] === 'OK' ? 'Realizado con éxito' : 'Fallo el proceso...' ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>

						<div class="form-buttons">
							<?php if($continue): ?>
								<button type="submit" class="btn">Continuar</button>
							<?php else: ?>
								<button type="submit" class="btn ghost">Volver a verificar...</button>
							<?php endif; ?>
						</div>

					<?php elseif($started === 3 && $step === 'requirements'):
						$minima = $version_minima ?? '';
						$maxima = $version_maxima ?? '';
						$actual = $version_actual ?? '';
						$ok = $version_ok ?? '';
					?>
						<legend>Verificación de Requisitos del Sistema</legend>

						<div class="form-group">
							<div class="label">Versión de PHP:</div>
							<div class="group">
								<span>Requerida: <?= "$minima a $maxima"; ?> / Detectada: <?= $actual; ?></span>
								<div class="badge <?= $ok ? 'success' : 'danger' ?>">
									<?= $ok ? 'Correcta' : 'No compatible'; ?>
								</div>
							</div>
						</div>

						<div style="display: grid;grid-template-columns: repeat(3, 1fr); gap: 1rem">
						<?php foreach($status_extensions ?? [] as $ext => $instalada): ?>
							<div class="form-group" style="display: block;">
								<div class="label">Extension <strong><?= $ext ?></strong></div>
								<div class="badge <?= $instalada['css'] ?>">
									<?= $instalada['text'] === 'OK' ? 'Habilitada' : 'Faltante' ?>
								</div>
							</div>
						<?php endforeach; ?>
						</div>

						<div class="form-buttons">
							<?php if($continuar ?? false): ?>
								<button type="submit" class="btn">Continuar</button>
							<?php else: ?>
								<button type="submit" class="btn ghost">Volver a verificar...</button>
							<?php endif; ?>
						</div>

					<?php elseif($started === 4 && $step === 'database'): ?>

						<!-- DATOS DE CONEXIÓN CON LA BASE DE DATOS -->
						<legend>Base de datos</legend>
						<p>Ingresa tus datos de conexi&oacute;n a la base de datos.</p>
						<div class="form-group">
							<label for="hostname_db">Servidor:</label>
							<input type="text" autocomplete="off" id="hostname_db" name="hostname" value="<?= htmlspecialchars($default['hostname'] ?? '') ?>" placeholder="localhost" required aria-required="true"/>
							<span id="hostname_db_help" class="help-text">Donde est&aacute; la base de datos, ej: <strong>localhost</strong></span>
						</div>

						<div class="form-group">
							<label for="username_db">Nombre de usuario</label>
							<input type="text" id="username_db" name="username" value="<?= htmlspecialchars($default['username'] ?? '') ?>" placeholder="root" required aria-required="true">
						</div>

						<div class="form-group">
							<label for="password_db">Contraseña</label>
							<input type="password" id="password_db" name="password" value="<?= htmlspecialchars($default['password'] ?? '') ?>" placeholder="••••••••" autocomplete="current-password">
						</div>

						<div class="form-group">
							<label for="database_db">Nombre de la base de datos:</label>
							<input type="text" autocomplete="off" id="database_db" name="database" value="<?= htmlspecialchars($default['database'] ?? '') ?>" placeholder="phpost_db" required aria-required="true"/>
						</div>

						<div class="form-buttons">
							<button type="submit" class="btn">Comprobar y continuar</button>
						</div>

					<?php elseif($started === 5 && $step === 'site'): ?>

						<!-- DATOS DEL SITIO -->
						<legend>Datos del sitio</legend>
						<p>Ingresa los datos de configuración para tu sitio, completarlo te llevará algunos minutos.</p>

						<h3>Datos principales y obligatorios</h3>
						<div class="form-group">
							<label for="data_site">Nombre del sitio</label>
							<input type="text" id="data_site" name="titulo" value="<?= htmlspecialchars($default['titulo'] ?? '') ?>" placeholder="<?= htmlspecialchars($app['title']) ?>" required>
						</div>
						<div class="form-group">
							<label for="data_slogan">Slogan del sitio</label>
							<input type="text" id="data_slogan" name="slogan" value="<?= htmlspecialchars($default['slogan'] ?? '') ?>" placeholder="Sumando inteligencia colectiva!" required>
						</div>
						<div class="form-group">
							<label for="data_url">Dirección</label>
							<input type="text" id="data_url" name="url" value="<?= htmlspecialchars($default['url'] ?? '') ?>" placeholder="<?= htmlspecialchars(getSiteUrl()) ?>" required>
						</div>
						<div class="form-group">
							<label for="data_email">Correo del sitio / Contacto</label>
							<input type="email" id="data_email" name="email_site" value="<?= htmlspecialchars($default['email'] ?? '') ?>" placeholder="hola@example.com" required>
						</div>

						<h3>Datos opcionales</h3>
						<p>Necesitas las claves de reCaptcha v3 para usarlo.</p>
						<div class="form-group">
							<label for="data_pkey">Clave pública</label>
							<input type="text" id="data_pkey" name="pkey" value="<?= htmlspecialchars($default['pkey'] ?? '') ?>" placeholder="6LfFFiMdAAAAAAQjDafWXZ0FeyesKYjVm4DSUoao">
						</div>
						<div class="form-group">
							<label for="data_skey">Clave secreta</label>
							<input type="text" id="data_skey" name="skey" value="<?= htmlspecialchars($default['skey'] ?? '') ?>" placeholder="6LfFFiMdAAAAAFIP4oNFLQx5Fo1FyorTzNps8ChE">
						</div>

						<div class="form-buttons">
							<button type="submit" class="btn">Continuar</button>
						</div>

					<?php elseif($started === 6 && $step === 'administrator'): ?>

						<!-- DATOS DEL ADMINISTRADOR -->
						<legend>Datos del administrador</legend>
						<p>Ingresa tus datos para crear tu usuario administrador.</p>
						<div class="form-group">
							<label for="user_username">Nombre de usuario</label>
							<input type="text" id="user_username" name="username" value="<?= htmlspecialchars($default['username'] ?? '') ?>" placeholder="JhonDoe" required>
						</div>
						<div class="form-group">
							<label for="user_password">Contraseña del usuario</label>
							<input type="password" id="user_password" name="password" value="" placeholder="Mín. 8 + mayúscula + número" required autocomplete="new-password">
						</div>
						<div class="form-group">
							<label for="user_confirmar">Confirmar contraseña</label>
							<input type="password" id="user_confirmar" name="confirm" value="" placeholder="Repite la contraseña" required autocomplete="new-password">
						</div>
						<div class="form-group">
							<label for="user_email">Tu correo</label>
							<input type="email" id="user_email" name="email" value="<?= htmlspecialchars($default['email'] ?? '') ?>" placeholder="jhondoe@example.com" required>
						</div>

						<div class="form-buttons">
							<button type="submit" class="btn">Continuar</button>
						</div>

					<?php elseif($started === 7 && $step === 'installing'): ?>
						<!-- REVISIÓN + INSTALACIÓN EN VIVO POR AJAX -->
						<legend>Revisando datos...</legend>
						<p>Revisa los datos. Al pulsar <strong>Instalar</strong> verás el progreso en vivo.</p>

						<div id="data-wrap" style="display:block;">
							<?php foreach($data ?? [] as $heading => $items): ?>
								<h2 style="margin-bottom: 0;"><?= htmlspecialchars($heading) ?></h2>
								<ul class="data-wrap-list">
									<?php foreach($items as $k => $val): ?>
										<li>
											<strong style="text-transform: uppercase;"><?= htmlspecialchars($k) ?></strong>
											<span><?= htmlspecialchars((string)$val) ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endforeach; ?>
						</div>

						<div class="form-group" id="live-wrap" style="display:none;">
							<label for="install_live">Progreso de instalación</label>
							<div id="install-bar" class="progress">
								<div id="install-bar-fill"></div>
							</div>
							<textarea id="install_live" rows="14" readonly></textarea>
						</div>

						<div class="form-buttons" id="install-actions">
							<button type="button" id="btn-install" class="btn">Instalar</button>
							<a id="btn-finish" class="btn" href="./index.php?step=finish" style="display:none;text-align:center;text-decoration:none;">Ir al final</a>
						</div>
						<script>
							let tables = <?= json_encode($taskTables ?? []) ?>;
	    					let inserts = <?= json_encode($taskInserts ?? []) ?>;
						</script>
						<script src="<?= getSiteUrl() ?>/install/assets/js/installing.js"></script>

					<?php elseif($started === 8 && $step === 'finish'): ?>
						<legend>¡Listo!</legend>

						<div class="alert"><span class="alert-desc">Borra la carpeta <strong>install</strong> por seguridad antes de usar el sitio.</span></div>
						<p>
							Gracias por instalar <strong><?= $app['title'] ?></strong>, ya est&aacute; lista tu nueva comunidad <strong>Link Sharing System</strong>. S&oacute;lo inicia sesi&oacute;n con tus datos y comienza a disfrutar. Ahora no dejes de <a href="<?= $app['others']['original'] ?>" target="_blank" rel="noopener noreferrer" aria-label="Ir al repositorio original de PHPost en GitHub"><u>visitarnos</u></a> para estar pendiente de futuras actualizaciones. Recuerda reportar cualquier bug que encuentres, de esta manera todos ganamos.
						</p>
						<?php if(!empty($install_log)): ?>
							<div class="form-group">
								<label for="install_log_final">Registro final</label>
								<textarea id="install_log_final" rows="10" readonly style="font-family:monospace;white-space:pre;"><?= htmlspecialchars(implode("\n", $install_log)) ?></textarea>
							</div>
						<?php endif; ?>
						<div class="form-buttons">
							<a class="btn" href="<?= htmlspecialchars(rtrim($siteData['url'] ?? getSiteUrl(), '/')) ?>" style="text-align:center;text-decoration:none;">Ir al sitio</a>
						</div>
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

	<template id="alert">
		<div class="alert" role="alert">
			<div>
				<div class="alert-title">Atención requerida</div>
				<div class="alert-desc">La base de datos especificada ya contiene tablas existentes. Al continuar, podrían sobrescribirse.</div>
			</div>
		</div>
	</template>

</body>
</html>
