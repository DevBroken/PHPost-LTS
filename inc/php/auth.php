<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

// Lo busca en frontend/auth/layout.tpl
$tsPage = "layout";

$tsLevel = 'visitantes';

$tsAjax = !isset($_GET['ajax']);

$tsContinue = true;

// Para no usar el prefijo t.
$usePrefix = false;

require_once dirname(__DIR__, 2) . "/header.php";

// Incluimos el título a la página
$tsTitle = "Bienvenidos a {$tsCore->settings['titulo']}";

if($tsUser->is_member) {
	header("Location: {$tsCore->settings['url']}");
	die;
}

// Nivel y permisos de acceso
$tsLevelMsg = $tsUser->setLevel(
		level: $tsLevel
	);
if($tsLevelMsg > 1) {
	$tsPage = 'aviso';
	$tsContinue = false;
	$smarty->assign("tsAviso", $tsLevelMsg);
}

$action = trim($_GET['action'] ?? '');

/**
 * Si no hay problemas, continuamos
*/
if ($tsContinue) {

	$smarty->assign("tsAction", $action);
	$smarty->assign("isLogin", ($action === 'login'));
	$smarty->assign("tsHeading", ($action === 'login' ? 'Inciar sesión' : 'Crear cuenta'));

	if($action === 'register') {
		require_once TS_CLASS . '/c.registro.php';
		$tsRegistro = $container->get(tsRegistro::class);

		$smarty->assign('tsRegisterOpen', (int)$tsRegistro->register['c_reg_active'] === 1);
		$smarty->assign('tsRegisterPKey', (string)$tsRegistro->public_key);
	}

}

if($tsAjax)
{
	// Asignamos título
	$smarty->assign("tsTitle",$tsTitle);
	// Incluir footer
	require_once TS_ROOT . "/footer.php";
}