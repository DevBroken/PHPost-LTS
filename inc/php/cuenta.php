<?php 
/**
 * Controlador
 *
 * @name    cuenta.php
 * @author  PHPost Team
*/

/**********************************\

*	(VARIABLES POR DEFAULT)		*

\*********************************/

	$tsPage = "cuenta";	// tsPage.tpl -> PLANTILLA PARA MOSTRAR CON ESTE ARCHIVO.

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

	$action = $_GET['action'];
	//
	include("../class/c.cuenta.php");
	$tsCuenta = new tsCuenta();

/**********************************\

*	(INSTRUCCIONES DE CODIGO)		*

\*********************************/

	if(empty($action)){
		require_once TS_EXTRA . "/datos.php";
		require_once TS_EXTRA . "/paises.php";
		require_once TS_EXTRA . "/geodata.php";
		// SOLO MENORES DE 100 AÑOS xD Y MAYORES DE...
		$now_year = date("Y",time());
		$max_year = 100 - $tsCore->allows['c_allow_edad'];
		$end_year = $now_year - $tsCore->allows['c_allow_edad'];
		$smarty->assign("tsMax",$max_year);
		$smarty->assign("tsEndY",$end_year);
		// PERFIL INFO
      $tsPerfil = $tsCuenta->loadPerfil();
		$smarty->assign("tsPerfil",$tsPerfil);
		// PERFIL DATA
		$smarty->assign("tsPData",$tsPerfilData);
      $smarty->assign("tsPrivacidad",$tsPrivacidad);
		// DATOS
		$smarty->assign("tsPaises",$tsPaises);
		$smarty->assign("tsEstados",$estados[$tsPerfil['user_pais']]);
		$smarty->assign("tsMeces",$tsMeses);
      // BLOQUEOS
      $smarty->assign("tsBlocks",$tsCuenta->loadBloqueos());
        
	} elseif($action == 'save'){
		echo $tsCore->setJSON($tsCuenta->savePerfil());
	} elseif($action == 'desactivate'){
		if(!empty($_POST['validar'])) echo $tsCuenta->desCuenta();
	}
   $smarty->assign("tsAccion", $_GET["accion"]);
	
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