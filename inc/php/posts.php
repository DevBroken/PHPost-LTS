<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

$tsPage = "posts";
$tsLevel = 'todos';
$tsAjax = !isset($_GET['ajax']);
$tsContinue = true;

require_once dirname(__DIR__, 2) . "/header.php";

$tsTitle = $tsCore->settings['titulo'];

/*
 * -------------------------------------------------------------------
 *  Validando nivel de acceso
 * -------------------------------------------------------------------
 */

// Nivel y permisos de acceso
$tsLevelMsg = $tsUser->setLevel(
    level: $tsLevel
);
if($tsLevelMsg != 1) {
	$tsPage = 'aviso';
	# ya no usa $tsAjax = 0
	$smarty->assign("tsAviso",$tsLevelMsg);

	$tsContinue = false;
}

if($tsContinue) {

    // Afiliados
    require_once TS_CLASS . "/c.afiliado.php";
    require_once TS_CLASS . "/c.posts.php";

    $tsAfiliado = $container->get(tsAfiliado::class);
    $tsPosts = $container->get(tsPosts::class);

    // Referido?
    if(!empty($_GET['ref']))
        $tsAfiliado->urlIn();

    // Category
	$category = $_GET['categoria'] ?? '';

    // Post anterior/siguiente
    if(isset($_GET['action']) && in_array($_GET['action'], ['next', 'prev', 'random'], true)) {
        $tsPosts->redirectToPostNav();
    }

    // DATOS DEL POST
    $tsPost = $tsPosts->getPost();
    //
    if($tsPost['post_id'] > 0) {
    	// TITULO NUEVO
    	$tsTitle = $tsPost['post_title'].' - '.$tsTitle;
    	// ASIGNAMOS A LA PLANTILLA
    	$smarty->assign("tsPost",$tsPost);
    	// DATOS DEL AUTOR
    	$smarty->assign("tsAutor",$tsPosts->getAutor($tsPost['post_user']));
		// DATOS DEL RANGO DEL PUTEADOR
		$smarty->assign("tsPunteador",$tsPosts->getPunteador());
    	// RELACIONADOS
    	$tsRelated = $tsPosts->getRelated($tsPost['post_tags']);
    	$smarty->assign("tsRelated",$tsRelated);
    	// COMENTARIOS
    	/*$tsComments = $tsPosts->getComentarios($tsPost['post_id']);
    	$tsComments = array('num' => $tsComments['num'], 'data' => $tsComments['data']);
    	$smarty->assign("tsComments",$tsComments);*/
    	// PAGINAS
    	$total = (int)($tsPost['post_comments'] ?? 0);
    	$tsPages = Paginador::getPages($total, (int)($tsCore->settings['c_max_com'] ?? 0));
    	$tsPages['post_id'] = $tsPost['post_id'];
    	$tsPages['autor'] = $tsPost['post_user'];
    	//
    	$smarty->assign("tsPages",$tsPages);
    
    } else {
    	//
        if($tsPost[0] == 'privado'){
            $tsTitle = $tsPost[1].' - '.$tsTitle;
            $tsPage = "registro";
        } else {
    		$tsTitle = $tsTitle.' - '.$tsCore->settings['slogan'];
    		//
    		$tsPage = "post.aviso";
    		# ya no usa $tsAjax = 0
    		$smarty->assign("tsAviso",$tsPost);
    		//
    		$title = str_replace("-",",",$tsCore->setSecure($_GET['title']));
    		$title = explode(",",$title);
    		// RELACIONADOS
    		$tsRelated = $tsPosts->getRelated($title);
    		$smarty->assign("tsRelated",$tsRelated);
        }
    }


}
/*
 * -------------------------------------------------------------------
 *  Incluir plantilla
 * -------------------------------------------------------------------
 */

if($tsAjax)
{
    // Asignamos título
	$smarty->assign("tsTitle",$tsTitle);
    // Incluir footer
	include(TS_ROOT . "/footer.php");
}