<?php 
/**
 * Controlador
 *
 * @name    usuarios.php
 * @author  PHPost Team
*/
/**********************************\

*	(VARIABLES POR DEFAULT)		*

\*********************************/

	$tsPage = "usuarios";	// tsPage.tpl -> PLANTILLA PARA MOSTRAR CON ESTE ARCHIVO.

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



/**********************************\

*	(INSTRUCCIONES DE CODIGO)		*

\*********************************/
    // PAICES
    require_once TS_EXTRA . "/paises.php";
    $smarty->assign("tsPaises",$tsPaises);
    // USUARIOS
    $tsUsers = $tsUser->getUsuarios();
    $smarty->assign("tsUsers",$tsUsers['data']);
    $smarty->assign("tsPages",$tsUsers['pages']);
    $smarty->assign("tsTotal",$tsUsers['total']);
    // FILTROS
    $smarty->assign("tsFiltro",array('online' => $_GET['online'], 'avatar' => $_GET['avatar'], 'sex' => $_GET['sexo'], 'pais' => $_GET['pais'], 'rango' => $_GET['rango']));
    // RANGOS
	$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT rango_id, r_name FROM u_rangos ORDER BY rango_id');
    $smarty->assign("tsRangos",result_array($query));
    

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