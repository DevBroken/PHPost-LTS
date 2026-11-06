<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

$tsPage = "home";

$tsLevel = 'todo';

$tsAjax = !isset($_GET['ajax']);

$tsContinue = true;

$tsTitle = $tsCore->settings['titulo'].' - '.$tsCore->settings['slogan'];

/*
 * -------------------------------------------------------------------
 *  Validando nivel de acceso
 * -------------------------------------------------------------------
 */

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

	// Afiliados
	require_once TS_CLASS . "/c.afiliado.php";
	require_once TS_CLASS . "/c.fotos.php";
	require_once TS_CLASS . "/c.posts.php";
	require_once TS_CLASS . "/c.home.php";
	require_once TS_CLASS . "/c.tops.php";

	$tsAfiliado = $container->get(tsAfiliado::class);
	$tsFotos    = $container->get(tsFotos::class);
	$tsHome     = $container->get(tsHome::class);
	$tsTops     = $container->get(tsTops::class);

	// Referido?
	if(!empty($_GET['ref'])) $tsAfiliado->urlIn();

	// Category
	$category = $_GET['categoria'] ?? '';
	$tsHome->category = $category;

	// CATEGORIA
	if(!empty($category)) {
		$catData = $tsHome->getCategoryData();
		$tsTitle = $tsCore->settings['titulo'].' - '.$catData['c_nombre'];
		$smarty->assign("tsCatData", $catData);
	}

	$cursor = isset($_GET['cursor']) ? (string)$_GET['cursor'] : null;

	$tsLastPosts = $tsHome->getLastPosts(false, $cursor);

	$paginacionHtml = '';
	if ($tsLastPosts['tipo'] === 'paginas') {
	    $numPerPage = (int)($tsCore->settings['c_max_posts'] ?? 20);
	    $maxValue = $tsLastPosts['meta']['total'];
	    $paginacionHtml = Paginador::getPagination($maxValue, $numPerPage);
	}

	$smarty->assign("tsPosts", $tsLastPosts);
	$smarty->assign("tsPostsPages", $paginacionHtml);

	if ($cursor === null && !isset($_GET['page'])) {
	    $tsLastStickys = $tsHome->getLastPosts(true);
	    $smarty->assign("tsPostsStickys", $tsLastStickys['data']);
	}

	// CAT
	$smarty->assign("tsCat", $category);
	$smarty->assign("tsStats", $tsTops->getStats());
	// ULTIMOS COMENTARIOS
	$smarty->assign("tsComments", $tsHome->getLastComentarios());
	// TOP POSTS
	$smarty->assign("tsTopPosts", $tsTops->getHomeTopPosts());
	// TOP USERS
	$smarty->assign("tsTopUsers", $tsTops->getHomeTopUsers());

	// IMAGENES
	$tsImages = $tsFotos->getLastFotos();
	$smarty->assign("tsImages",$tsImages);
	$smarty->assign("tsImTotal",count($tsImages));
	// AFILIADOS
	$smarty->assign("tsAfiliados",$tsAfiliado->getAfiliados());
	// DO <= PARA EL MENU
	$smarty->assign("tsDo", $_GET['do'] ?? '');
}

if($tsAjax)
{
	// Asignamos título
	$smarty->assign("tsTitle",$tsTitle);
	// Incluir footer
	require_once TS_ROOT . "/footer.php";
}