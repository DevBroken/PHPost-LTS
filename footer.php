<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

defined('TS_HEADER') OR exit('No se permite el acceso directo al script');

$smarty->assign("tsPage", $tsPage);

$usePrefix = isset($usePrefix) ? $usePrefix : true;

$smarty->templateError = "t.error404.tpl";
$smarty->setTheme();
$smarty->setPage($tsPage);
$smarty->load($usePrefix);
