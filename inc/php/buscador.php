<?php 
/**
 * Controlador
 *
 * @name    buscador.php
 * @author  PHPost Team
*/
/**********************************\

*	(VARIABLES POR DEFAULT)		*

\*********************************/

	$tsPage = "buscador";	// tsPage.tpl -> PLANTILLA PARA MOSTRAR CON ESTE ARCHIVO.

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

	$q = htmlspecialchars($_GET['q']);
    $e = htmlspecialchars($_GET['e']);
    $c = (int)$_GET['cat'];
    $a = htmlspecialchars($_GET['autor']);
	//
	include("../class/c.posts.php");
	$tsPosts = new tsPosts();

/**********************************\

*	(INSTRUCCIONES DE CODIGO)		*

\*********************************/

	if((!empty($q) || !empty($a)) && $e != 'google'){
	   $smarty->assign("tsResults",$tsPosts->getQuery());
	}
	//
    $smarty->assign("tsQuery",$q);
    $smarty->assign("tsEngine",$e);
    $smarty->assign("tsCategory",$c);
    $smarty->assign("tsAutor",$a);
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