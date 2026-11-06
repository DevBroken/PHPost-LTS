<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

defined('TS_HEADER') OR define('TS_HEADER', TRUE);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/inc/libs/autoload.php';

/*
 * -------------------------------------------------------------------
 *  Agregamos los archivos globales
 * -------------------------------------------------------------------
 */

require_once TS_EXTRA . '/functions.php';

// Constantes
require_once TS_UTILS . '/Misc/Constants.php';
// Utilidades centrales por dominio
// De momento estaran aquí, luego lo moveré
require_once TS_UTILS . '/Http/Http.php';
require_once TS_UTILS . '/Http/HttpClient.php';
require_once TS_UTILS . '/Http/Response.php';
require_once TS_UTILS . '/Logger/Logger.php';
require_once TS_UTILS . '/Misc/Container.php';
require_once TS_UTILS . '/Paginator/Paginador.php';
require_once TS_UTILS . '/Password/Password.php';
require_once TS_UTILS . '/Request/Request.php';
require_once TS_UTILS . '/Seguridad/IP.php';
require_once TS_UTILS . '/Seguridad/Security.php';

// Nucleo
require_once TS_CLASS . '/c.core.php';
require_once TS_CLASS . '/c.emails.php';
require_once TS_CLASS . '/c.actividad.php';
require_once TS_CLASS . '/c.mensajes.php';
require_once TS_CLASS . '/c.monitor.php';
require_once TS_CLASS . '/c.smarty.php';
require_once TS_CLASS . '/c.user.php';
require_once TS_CLASS . '/c.visitas.php';

/*
 * -------------------------------------------------------------------
 *  Inicializamos los objetos principales
 * -------------------------------------------------------------------
 */

cleanRequest();
$container = new Container;

$Logger = $container->get(Logger::class);
set_error_handler([$Logger, 'handlePhpError'], E_USER_DEPRECATED);

$tsCore        = $container->get(tsCore::class);
$tsUser        = $container->get(tsUser::class);
$tsMonitor 	   = $container->get(tsMonitor::class);
$tsActividad   = $container->get(tsActividad::class);
$tsMP          = $container->get(tsMensajes::class);
$tsVisitas     = $container->get(tsVisitas::class);
$smarty        = $container->get(tsSmarty::class);
$tsEmail       = $container->get(tsEmail::class);

// Definimos el template a utilizar
define('TS_TEMA', $tsCore->settings['tema']['t_path'] ?? 'default');

/*
 * -------------------------------------------------------------------
 *  Asignacion de variables
 * -------------------------------------------------------------------
*/
// Configuraciones
$smarty->assign('tsConfig', $tsCore->settings);
$smarty->assign('tsAllows', $tsCore->allows);
$smarty->assign('tsRoutes', $tsCore->getRoutes());
$smarty->assign('tsCategorias', $tsCore->getCategorias());
$smarty->assign('tsUser', $tsUser);
$smarty->assign('tsAvisos', $tsMonitor->avisos);
$smarty->assign('tsNots', $tsMonitor->notificaciones);
$smarty->assign('tsMPs', $tsMP->mensajes);

/**
 * Si hay alguna IP bloqueada por el Moderador/Administrador,
 * ejecutamos esta funcion, en caso contrario no hara nada
*/
if($tsUser->isUserIPBlocked()) die('Tu IP fue bloqueada por el administrador/moderador.');

/**
 * Si hay un usuario baneado por el Moderador/Administrador,
 * ejecutamos esta funcion, en caso contrario no hara nada
*/
$banned_data = $tsUser->getUserBanned();
if(!empty($banned_data)) {
   if(empty($_GET['action'])){
      $smarty->assign([
         'tsTitle' => "Usuario baneado - {$tsCore->settings['titulo']}",
         'tsBanned' => $banned_data
      ]);
      $smarty->loadFilter('output', 'trimwhitespace');
      $smarty->display('suspension.tpl');

   } else die('<div class="emptyError">Usuario suspendido</div>');
   //
   exit;
}

/**
 * Si la pagina esta en modo mantenimiento, ejecutamos la funcion
*/
if($tsCore->settings['offline'] === 1 && ($tsUser->is_admod !== 1 && $tsUser->permisos['govwm'] === false) && $_GET['action'] !== 'login-user'){
   $smarty->assign('tsTitle', "Sitio en mantenimiento - {$tsCore->settings['titulo']}");
   $smarty->assign('tsLogin', (isset($_GET["login"]) and $_GET["login"] == 'admin' ? true : false));

   if(empty($_GET["action"])) {
      $smarty->loadFilter('output', 'trimwhitespace');
      $smarty->display('mantenimiento.tpl');
   } else die('Espera un poco...');
   exit();
}