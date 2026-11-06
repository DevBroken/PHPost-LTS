<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$tsTitle}</title>
<link rel="shortcut icon" href="{$tsRoutes['tema']['images']}/favicon.ico" type="image/x-icon" />
<link rel="preload" href="{$tsRoutes['assets']['fonts']}/RobotoCondensed.woff2" as="font" type="font/woff2" crossorigin="anonymous">
<link rel="stylesheet" href="{$tsRoutes['assets']['css']}/tokens.css?{$smarty.now}" type="text/css">
<link rel="stylesheet" href="{$tsRoutes['assets']['css']}/global.css?{$smarty.now}" type="text/css">
<script defer>
    const global_data = Object.freeze({
        url: "{$tsConfig.url}"
    });
</script>
{if $tsAction == 'register'}<script src="https://www.google.com/recaptcha/api.js?render={$tsRegisterPKey}"></script>
{/if}
<script src="{$tsRoutes['assets']['js']}/jquery.min.js" defer></script>
<script src="{$tsRoutes['assets']['js']}/plugins.js" defer></script>
<script src="{$tsRoutes['assets']['js']}/{$tsAction}.js" defer></script>
</head>
<body>

    {include "auth/{$tsAction}.tpl"}

</body>
</html>