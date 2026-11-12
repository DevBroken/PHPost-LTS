<?php 
/**
 * Controlador
 *
 * @name    pages.php
 * @author  PHPost Team
*/
/**********************************\

*	(VARIABLES POR DEFAULT)		*

\*********************************/

	$tsPage = "pages";	// tsPage.tpl -> PLANTILLA PARA MOSTRAR CON ESTE ARCHIVO.

	$tsLevel = 'todos';		// NIVEL DE ACCESO A ESTA PAGINA. => VER FAQs

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
    $action = $_GET['action'];
/**********************************\

*	(INSTRUCCIONES DE CODIGO)		*

\*********************************/

    switch($action){
        case 'ayuda':
        break;
        case 'chat':
        break;
        case 'contact':
        break;
        case 'protocolo':
        break;
        case 'terminos-y-condiciones':
        break;
        case 'privacidad':
        break;
        case 'dmca':
        break;
        default:
        $tsCore->navigate('/');
        break;
    }
    //
    $smarty->assign("tsAction",$action);
/**********************************\

* (AGREGAR DATOS GENERADOS | SMARTY) *

\*********************************/
	}

if(empty($tsAjax)) {	// SI LA PETICION SE HIZO POR AJAX DETENER EL SCRIPT Y NO MOSTRAR PLANTILLA, SI NO ENTONCES MOSTRARLA.

	$smarty->assign("tsTitle",$tsTitle);	// AGREGAR EL TITULO DE LA PAGINA ACTUAL

	/*++++++++ = ++++++++*/
	require_once TS_ROOT . '/footer.php';
	/*++++++++ = ++++++++*/
}