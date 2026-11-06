<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$tsTitle}</title>
<link rel="shortcut icon" href="{$tsRoutes['tema']['images']}/favicon.ico" type="image/x-icon" />
<link rel="prefetch" href="{$tsRoutes['assets']['fonts']}/RobotoCondensed.woff2" type="font/woff2" crossorigin="anonymous">
<link href="{$tsRoutes['assets']['css']}/tokens.css" rel="stylesheet" type="text/css" />
<link href="{$tsRoutes['tema']['base']}/styles.css" rel="stylesheet" type="text/css" />
<script defer>
	const global_data = Object.freeze({
		user_key: {$tsUser->uid|default:0},
		postid: {$tsPost.post_id|default:0},
		fotoid: {$tsFoto.foto_id|default:0},
		s_title: "{$tsConfig.titulo}",
		s_slogan: "{$tsConfig.slogan}",
		// Acciones
		notifica: {$tsNots|default:0},
		mensaje: {$tsMPs|default:0},
		action: "{$tsAction|default:''}",
		// Enlaces
		url: "{$tsConfig.url}",
		domain: "{$tsRoutes['domain']}",
		img: "{$tsRoutes['tema']['images']}",
		smiles: "{$tsRoutes['assets']['images']}/smiles",
	});
</script>
<script src="{$tsRoutes['assets']['js']}/jquery.min.js" defer></script>
<script src="{$tsRoutes['assets']['js']}/plugins.js" defer></script>
<script src="{$tsRoutes['tema']['js']}/acciones.js" defer></script>
{if $tsUser->hasPermisson()}
<script src="{$tsRoutes['tema']['js']}/moderacion.js" defer></script>
{/if}
{if $tsAllows.c_allow_live}
<link href="{$tsRoutes['tema']['css']}/live.css" rel="stylesheet" type="text/css" />
<script src="{$tsRoutes['tema']['js']}/live.js" defer></script>
{/if}
{if $tsPage == 'agregar' || $tsPage == 'posts'}
<link href="{$tsRoutes['tema']['css']}/wysibb.css" rel="stylesheet" type="text/css" />
<script src="{$tsRoutes['tema']['js']}/wysibb.js" defer></script>
{/if}
</head>
<body>
{if $tsUser->is_admod == 1}{$tsConfig.install}{/if}
<!--JAVASCRIPT-->
<div id="loading" style="display:none">
	<img src="{$tsConfig.tema}/images/ajax-loader.gif" alt="Cargando"> Procesando...
</div>
<div id="swf"></div>
<div id="js" style="display:none"></div>
<div id="mask"></div>
<div id="mydialog"></div>
<div class="UIBeeper" id="BeeperBox"></div>
<div id="brandday">
	<div class="rtop"></div>
	<div id="maincontainer">
		<!--MAIN CONTAINER-->
		<div id="head">
			<div id="logo">
				<a id="logoi" title="{$tsConfig.titulo}" href="{$tsConfig.url}">
					<img border="0" align="top" title="{$tsConfig.titulo}" alt="{$tsConfig.titulo}" src="{$tsRoutes['tema']['images']}/space.gif">
				</a>
			</div>
			<div id="banner">
				{if $tsPage == 'posts' && $tsPost.post_id}
					{include file='m.global_search.tpl'}
				{else}
					{include file='m.global_ads_468.tpl'}
				{/if}
			</div>
		</div>
		<div id="contenido_principal">
		  {include file='head_menu.tpl'}
		  {include file='head_submenu.tpl'}
		  {include file='head_noticias.tpl'}
		  <div id="cuerpocontainer">
		  <!--Cuperpo-->