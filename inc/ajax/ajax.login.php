<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

const ACTIONS = [
	'login-user'	=> ['nivel' => 'miembro', 'plantilla' => '', 'ajax' => false],
	'login-activar'	=> ['nivel' => 'miembro', 'plantilla' => '', 'ajax' => false],
	'login-salir'	=> ['nivel' => 'miembro', 'plantilla' => '', 'ajax' => false],
];

if (!array_key_exists($action, ACTIONS)) {
	http_response_code(403);
	exit('Acción inválida');
}

$config = ACTIONS[$action];

$tsAjax  = (bool) $config['ajax'];
$tsPage  = sprintf('ajax/p.login.%s', $config['plantilla']);

// DEPENDE EL NIVEL
$tsLevelMsg = $tsUser->setLevel($config['nivel'], true);
if(!$tsLevelMsg) {
	echo '0: '.$tsLevelMsg['mensaje'];
	die();
}

# header('Content-Type: application/json; charset=utf-8');

// Comprobamos en la lista blanca
$extra_keys = array_diff(
	array_keys($_POST), // Nombres de las claves para comparar
	['nickname', 'password', 'remember', 'artscript'] // Lista blanca
);
if (!empty($extra_keys)) {
    Response::json("0: Parámetros manipulados.");
}

if (!empty($_POST['artscript'])) {
    Response::json("0: Acceso denegado.");
}

// CODIGO
switch($action){
	case 'login-user':
		echo $tsUser->loginUser(
			username: trim((string)$_POST['nickname'] ?? ''),
			password: (string)$_POST['password'],
			remember: (bool)($_POST['remember'] ?? false)
		);
	break;
	case 'login-activar':
		$activar = $tsUser->userActivate();
		# La clave en claro ya no existe (solo hashes), abrimos sesión directamente
		if(!$activar && !$tsUser->loginAfterActivation($activar)) {
			$tsPage = "aviso";
			$smarty->assign("tsAviso", [
				'titulo' => 'Error al activar tu cuenta',
				'mensaje' => 'El c&oacute;digo de validaci&oacute;n es incorrecto.'
			]);
			exit;
		}
		$tsCore->navigate('/cuenta/');
	break;
	case 'login-salir':
		$tsUser->logoutUser($tsUser->uid);
	break;
}