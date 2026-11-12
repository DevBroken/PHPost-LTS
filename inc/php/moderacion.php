<?php 
/**
 * Controlador
 *
 * @name    moderacion.php
 * @author  PHPost Team
*/
/**********************************\

*	(VARIABLES POR DEFAULT)		*

\*********************************/

	$tsPage = "moderacion";	// tsPage.tpl -> PLANTILLA PARA MOSTRAR CON ESTE ARCHIVO.

	$tsLevel = 'moderador';		// NIVEL DE ACCESO A ESTA PAGINA. => VER FAQs

	$tsAjax = !isset($_GET['ajax']); // LA RESPUESTA SERA AJAX?
	
	$tsContinue = true;	// CONTINUAR EL SCRIPT
	
/*++++++++ = ++++++++*/

	require_once dirname(__DIR__, 2) . "/header.php"; // INCLUIR EL HEADER

	$tsTitle = $tsCore->settings['titulo'].' - '.$tsCore->settings['slogan']; 	// TITULO DE LA PAGINA ACTUAL

/*++++++++ = ++++++++*/

	// VERIFICAMOS EL NIVEL DE ACCSESO ANTES CONFIGURADO
	$tsLevelMsg = $tsUser->setLevel(
		level: $tsLevel
	);
	if($tsLevelMsg > 1) {
		$tsPage = 'aviso';
		# ya no usa $tsAjax = 0
		$smarty->assign("tsAviso",$tsLevelMsg);
		//
		$tsContinue = false;
	}
	//
	if($tsContinue){

/**********************************\

* (VARIABLES LOCALES ESTE ARCHIVO)	*

\*********************************/

	// ACTION
	$action = htmlspecialchars($_GET['action']);
	// ACTION 2
	$act = htmlspecialchars($_GET['act']);
	// CLASE POSTS
	include("../class/c.moderacion.php");
	$tsMod = new tsMod();

/**********************************\

*	(INSTRUCCIONES DE CODIGO)		*

\*********************************/

	$denuncia = ['posts', 'users', 'mps', 'fotos'];

	if($action == ''){
		$smarty->assign("plantilla", "mod_welcome");
		$smarty->assign("tsMods",$tsMod->getMods());
    // DENUNCIAS
	} elseif(in_array($action, $denuncia)) {
		$smarty->assign("plantilla", "mod_report_" . $action);
      // DATOS EXTRA
      include('../ext/datos.php');
      // SEGUNDA ACCION
		if(empty($act)){
		 	$smarty->assign("tsReports", $tsMod->getDenuncias($action));
         $smarty->assign("tsDenuncias", $tsDenuncias[$action]);
		} elseif($act == 'info') {
         $smarty->assign("tsDenuncia", $tsMod->getDenuncia($action));
         $smarty->assign("tsDenuncias", $tsDenuncias[$action]);
		}
	}
   // SUSPENSIONES
   elseif($action == 'banusers'){
		$smarty->assign("plantilla", "mod_ban_users");
      $smarty->assign("tsSuspendidos",$tsMod->getSuspendidos());
   }
	//PAPELERAS
	elseif($action == 'pospelera'){
		$smarty->assign("plantilla", "mod_papelera_posts");
      $smarty->assign("tsPospelera",$tsMod->getPospelera());
   }
	elseif($action == 'fopelera'){
		$smarty->assign("plantilla", "mod_papelera_fotos");
       $smarty->assign("tsFopelera",$tsMod->getFopelera());
   }
	// CONTENIDO DESAPROBADO
	elseif($action == 'comentarios'){
		$smarty->assign("plantilla", "mod_revision_comentarios");
      $smarty->assign("tsComentarios",$tsMod->getComentariosD());
   }
	elseif($action == 'revposts'){
		$smarty->assign("plantilla", "mod_revision_posts");
      $smarty->assign("tsPosts",$tsMod->getPostsD());
   }
	// BUSCADOR DE IP Y CONTENIDO
   elseif($action == 'buscador') {
		$smarty->assign("plantilla", "mod_buscador");
		if($_POST['buscar']) {
			$tsCore->navigate('/moderacion/buscador/'.$_POST['m'].'/'.$_POST['t'].'/'.$_POST['texto']);
		}	
		if($act == 'search') $smarty->assign("tsContenido", $tsMod->getContenido()); 

	}

/**********************************\

* (AGREGAR DATOS GENERADOS | SMARTY) *

\*********************************/
	// ACCION?
	$smarty->assign("tsAction",$action);
	//
	$smarty->assign("tsAct",$act);
	//
	}

if(empty($tsAjax)) {	// SI LA PETICION SE HIZO POR AJAX DETENER EL SCRIPT Y NO MOSTRAR PLANTILLA, SI NO ENTONCES MOSTRARLA.

	$smarty->assign("tsTitle",$tsTitle);	// AGREGAR EL TITULO DE LA PAGINA ACTUAL
	
	$smarty->assign("tsSave",$_GET['save']);	// AGREGAR EL TITULO DE LA PAGINA ACTUAL
	
	/*++++++++ = ++++++++*/
	require_once TS_ROOT . '/footer.php';
	/*++++++++ = ++++++++*/
}