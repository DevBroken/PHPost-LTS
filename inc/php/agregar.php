<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 * @license MIT
 */

$tsPage = "agregar";

$tsLevel = 'miembro';

$tsAjax = !isset($_GET['ajax']);

$tsContinue = true;

require_once dirname(__DIR__, 2) . "/header.php"; // INCLUIR EL HEADER

$tsTitle = $tsCore->settings['titulo'].' - '.$tsCore->settings['slogan']; 	// TITULO DE LA PAGINA ACTUAL

// Nivel y permisos de acceso
$tsLevelMsg = $tsUser->setLevel(
		level: $tsLevel
	);
if($tsLevelMsg > 1) {
	$tsPage = 'aviso';
	$tsContinue = false;
	$smarty->assign("tsAviso", $tsLevelMsg);
}

if($tsContinue) {

	$action = trim($_GET['action'] ?? '');

	if(is_numeric($action)) {
		require_once TS_CLASS . '/c.borradores.php';
		$tsDrafts = $container->get(tsDrafts::class);
		$smarty->assign("tsDraft", $tsDrafts->getDraft());
		//
	} elseif($action === 'editar') {
		// CLASE
		require_once TS_CLASS . '/c.posts.php';
		$tsPosts = $container->get(tsPosts::class);

		// GUARDAR
		if(!empty($_POST['titulo'])) {
		  $post_save = $tsPosts->savePost();
			if($post_save == 1) {
				$tsPost = (int)$_GET['pid'];
				$tsCat = (int)$_POST['categoria'];
				$query = db_exec([__FILE__, __LINE__], 'query', "SELECT c.c_seo FROM p_categorias AS c WHERE c.cid = $tsCat LIMIT 1");
				$tsCat = db_exec('fetch_assoc', $query);
				
				//
				$post_url = "/posts/{$tsCat['c_seo']}/$tsPost/{$tsCore->setSEO($_POST['titulo'])}.html";
				// NOS VAMOS AL POST
				$tsCore->navigate($post_url);
			} else {
                $tsPage = 'aviso';
                $smarty->assign("tsAviso",array('titulo' => 'Oops!', 'mensaje' => $post_save, 'but' => 'Volver', 'link' => 'javascript:history.go(-1)'));
			}
		// EDITAR
		} else {
            $draft = $tsPosts->getEditPost();
            if(!is_array($draft)){
                $tsPage = 'aviso';
                $smarty->assign("tsAviso",array('titulo' => 'Opps...', 'mensaje' => $draft, 'but' => 'Ir a pagina principal', 'link' => "{$tsCore->settings['url']}"));
            }else $smarty->assign("tsDraft", $draft);
		}
		//
		$smarty->assign("tsAction",$_GET['action']);
		$smarty->assign("tsPid",$_GET['pid']);
		
	} elseif($_POST['titulo']) {
		// CLASE
		require_once TS_CLASS . '/c.posts.php';
		$tsPosts = $container->get(tsPosts::class);
		//
		$tsPost = $tsPosts->newPost();
		//
		$tsPage = 'aviso';
		# ya no usa $tsAjax = 0
		if($tsPost > 0) {
			$tsCat = (int)$_POST['categoria'];
			$query = db_exec([__FILE__, __LINE__], 'query', "SELECT c.c_seo FROM p_categorias AS c WHERE c.cid = $tsCat LIMIT 1");
			$tsCat = db_exec('fetch_assoc', $query);
			
			//
			$smarty->assign("tsAviso",array('titulo' => 'Bien!', 'mensaje' => 'El post <b>'.$_POST['titulo'].'</b> fue agregado. '.(!$tsUser->is_admod && ($tsUser->permisos['gorpap'] == true || $tsCore->settings['c_desapprove_post'] == 1) ? 'Deber&aacute; esperar su aprobaci&oacute;n' : '').' ', 'but' => 'Acceder al post', 'link' => "{$tsCore->settings['url']}/posts/{$tsCat['c_seo']}/$tsPost/{$tsCore->setSEO($_POST['titulo'])}.html"));
		} elseif($tsPost == -1){
			$smarty->assign("tsAviso",array('titulo' => 'Anti Flood', 'mensaje' => "No puedes realizar tantas acciones en tan poco tiempo. Vuelve a intentarlo en unos instantes.", 'but' => 'Volver', 'link' => "javascript:history.go(-1)"));
		} else {
			$smarty->assign("tsAviso",array('titulo' => 'Oops!', 'mensaje' => "Ha ocurrido un error intentalo m&aacute;s tarde.<br><b>Error</b>: ".$tsPost, 'but' => 'Volver', 'link' => 'javascript:history.go(-1)'));
		}
	}
	
}

if($tsAjax)
{
	// Asignamos título
	$smarty->assign("tsTitle",$tsTitle);
	$smarty->assign("tsSubmenu","agregar");
	// Incluir footer
	require_once TS_ROOT . "/footer.php";
}