<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

# INCLUIMOS EL HEADER
require_once dirname(__DIR__, 2) . "/header.php";

# INICIALIZAMOS LAS VARIABLES
$tsPage = "";
$tsLevel = 'todos';
$tsAjax = !isset($_GET['ajax']);
$tsTitle = $tsCore->settings['titulo'].' - '.$tsCore->settings['slogan'];
# Con esto evitamos que empiece con t.
$usePrefix = false;
# DESGLOSAMOS EL METODO ACTION
$action = trim($_GET['action'] ?? '');

[$actionType] = explode('-', $action, 2);
# QUE ARCHIVO NECESITAMOS?
$actionFile = __DIR__ . "/ajax.$actionType.php";

# Verificamos que exista el archivo
if(!file_exists($actionFile)) {
	http_response_code(404);
	header('Content-Type: text/html; charset=utf-8');
	die("0: No se encontro el archivo que se ha solicitado: 'ajax.{$actionType}.php'");
}

# Desde esta archivo se modificaran las variables
require_once $actionFile;

if($tsAjax) {
	$smarty->assign("tsTitle", $tsTitle);
	require_once TS_ROOT . '/footer.php';
}