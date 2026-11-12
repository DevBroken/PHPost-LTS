<?php 
/**
 * Controlador
 *
 * @name    monitor.php
 * @author  PHPost Team
*/

/**********************************\

*	(VARIABLES POR DEFAULT)		*

\*********************************/

	$tsPage = "monitor";	// tsPage.tpl -> PLANTILLA PARA MOSTRAR CON ESTE ARCHIVO.

	$tsLevel = 'miembro';		// NIVEL DE ACCESO A ESTA PAGINA. => VER FAQs

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

	$action = htmlspecialchars($_GET['action']);
	

/**********************************\

*	(INSTRUCCIONES DE CODIGO)		*

\*********************************/
        

		if(empty($action)){
            $tsMonitor->show_type = 2;
			$notificaciones = $tsMonitor->getNotificaciones();
			$smarty->assign("tsData",$notificaciones);
            // LIVE SOUND
            $smarty->assign("tsStatus",$_COOKIE);
            //
		} else {
			$smarty->assign("tsData",$tsMonitor->getFollows($action));
		}

/**********************************\

* (AGREGAR DATOS GENERADOS | SMARTY) *

\*********************************/
	//
	$smarty->assign("tsAction",$action);
	
	}

if(empty($tsAjax)) {	// SI LA PETICION SE HIZO POR AJAX DETENER EL SCRIPT Y NO MOSTRAR PLANTILLA, SI NO ENTONCES MOSTRARLA.

	$smarty->assign("tsTitle",$tsTitle);	// AGREGAR EL TITULO DE LA PAGINA ACTUAL

	/*++++++++ = ++++++++*/
	require_once TS_ROOT . '/footer.php';
	/*++++++++ = ++++++++*/
}