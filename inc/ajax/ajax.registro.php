<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

header('Content-Type: text/html; charset=utf-8');

const ACTIONS = [
	'registro-check-nickname'	=> ['nivel' => 'visitante', 'plantilla' => '', 'ajax' => false],
	'registro-check-email'		=> ['nivel' => 'visitante', 'plantilla' => '', 'ajax' => false],
	'registro-nuevo'	=> ['nivel' => 'visitante', 'plantilla' => '', 'ajax' => false],
];

if (!array_key_exists($action, ACTIONS)) {
	http_response_code(403);
	exit('Acción inválida');
}

$config = ACTIONS[$action];

$tsAjax  = (bool) $config['ajax'];
$tsPage  = sprintf('ajax/p.registro.%s', $config['plantilla']);

// DEPENDE EL NIVEL
$tsLevelMsg = $tsUser->setLevel($config['nivel'], true);
if($tsLevelMsg != 1) {
	echo '0: '.$tsLevelMsg;
	die();
}

require_once TS_CLASS . '/c.registro.php';
$tsRegistro = $container->get(tsRegistro::class);

$whitelist = ['nickname', 'email', 'password', 'password_confirm', 'terms', 'response'];
$clean = [];
foreach ($whitelist as $field) {
    $clean[$field] = isset($_POST[$field]) ? trim($_POST[$field]) : null;
}

$received = array_keys($_POST);
$compare = array_diff($received, $whitelist);
if (!empty($compare)) {
	Response::error("Petición malformada o alterada.");
}

match($action) {
	'registro-check-nickname', 'registro-check-email' => print $tsRegistro->checkUserEmail(),
	'registro-nuevo' => print $tsRegistro->registerUser()
};