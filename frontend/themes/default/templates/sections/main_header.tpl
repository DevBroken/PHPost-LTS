<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$tsTitle}</title>
<link rel="shortcut icon" href="{$Sources->assets('favicon.ico')}" type="image/x-icon" />
<link rel="stylesheet" href="{$Sources->assets('tokens.css')}">
<link rel="stylesheet" href="{$Sources->theme('components.css')}">
<link rel="stylesheet" href="{$Sources->theme('styles.css')}">
<link rel="stylesheet" href="{$Sources->assets('utilities.css')}">
{*<link rel="stylesheet" href="{$Sources->compiler('utilities.css', 'theme.css', true)}">*}
<script defer>
	const site = Object.freeze({
		title: "{$tsConfig.titulo}",
		slogan: "{$tsConfig.slogan}"
	})
	const global_data = Object.freeze({
		user_key: {$tsUser->uid|default:0},
		postid: {$tsPost.post_id|default:0},
		fotoid: {$tsFoto.foto_id|default:0},
		// Acciones
		notifica: {$tsNots|default:0},
		mensaje: {$tsMPs|default:0},
		action: "{$tsAction|default:''}",
		// Enlaces
		url: "{$tsConfig.url}",
		domain: "{$Sources->domain}",
		img: "{$Sources->route('assets', 'images')}",
		smiles: "{$Sources->route('assets', 'images')}/smiles",
	});
</script>
<script src="{$Sources->assets('jquery.min.js')}" defer></script>
<script src="{$Sources->assets('plugins.js')}" defer></script>
<script src="{$Sources->theme('acciones.js')}" defer></script>
{if $tsUser->hasPermisson()}
<script src="{$Sources->theme('moderacion.js')}" defer></script>
{/if}
{if $tsAllows.c_allow_live}
<script src="{$Sources->assets('live.js')}" defer></script>
{/if}
</head>
<body class="bg-gray-100">

	<div id="brandday" class="relative">
		<header class="sticky mt-0 t-0">
			{include "main_menu.tpl"}
			{*include "head_menu.tpl"}
			{include "head_submenu.tpl"*}
	    </header>
	    <main>
	        <section class="container mx-auto my-4 p-4 rounded border border-subtle bg-raised">
				{include "head_noticias.tpl"}