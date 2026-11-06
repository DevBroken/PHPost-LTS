<div class="clearbeta lastPosts">
    {if $tsPostsStickys}
    	<div class="header">
        	<div class="box_txt ultimos_posts">Posts importantes en {$tsConfig.titulo}</div>
            <div class="box_rss">
                <img src="{$tsRoutes['tema']['images']}/icons/note.png" />
            </div>
            <div class="clearBoth"></div>
        </div>
        <div class="body">
            <ul>
            	{foreach from=$tsPostsStickys item=p}
                <li {if $p.post_status == 3}style="background-color:#f1f1f1;"{elseif $p.post_status == 0}style="background-color:coral;"{elseif $p.post_status == 2}style="background-color:rosyBrown;"{elseif $p.user_activo == 0}style="background-color:burlyWood;"{elseif $p.user_baneado == 1}style="background-color:orange;"{/if} class="categoriaPost sticky{if $p.post_sponsored == 1} patrocinado{/if}">
                    <a {if $p.post_status == 3}class="qtip" title="El post est&aacute; en revisi&oacute;n"{elseif $p.post_status == 0}class="qtip" title="El post se encuentra en revisi&oacute;n por acumulaci&oacute;n de denuncias"{elseif $p.post_status == 2}class="qtip" title="El post est&aacute; eliminado"{elseif $p.user_activo == 0}class="qtip" title="La cuenta del usuario est&aacute; desactivada"{/if}  href="{$tsConfig.url}/c/{$p.c_seo}/{$p.post_slug}" style="background:url({$tsRoutes['tema']['images']}/icons/cat/{$p.c_img}) no-repeat 5px center" title="{$p.post_title}" target="_self" class="title">{$p.post_title|truncate:55}</a>
                </li>
                {/foreach}
            </ul>
        </div>
    {/if}
    <div class="header">
    	<div class="box_txt ultimos_posts">&Uacute;ltimos posts en {$tsConfig.titulo}</div>
        <div class="box_rss">
            <a href="/rss/ultimos-post">
                <span class="systemicons sRss" style="position:relative;z-index:87"></span>
            </a>
        </div>
        <div class="clearBoth"></div>
    </div>
    <div class="body">
        <ul>
            {if $tsPosts}
                {foreach from=$tsPosts.items item=p}
                    <li class="categoriaPost" style="background-image:url({$tsRoutes['tema']['images']}/icons/cat/{$p.c_img}); {if $p.post_status == 3} background-color:#f1f1f1; {elseif $p.post_status == 0}background-color:coral;{elseif $p.post_status == 2} background-color:rosyBrown;{elseif $p.user_activo == 0} background-color:burlyWood;{elseif $p.user_baneado == 1} background-color:orange;{/if}" >
                        <a {if $p.post_status == 3}class="qtip" title="El post est&aacute; en revisi&oacute;n"{elseif $p.post_status == 0}class="qtip" title="El post se encuentra en revisi&oacute;n por acumulaci&oacute;n de denuncias"{elseif $p.post_status == 2}class="qtip" title="El post est&aacute; eliminado"{elseif $p.user_activo == 0}class="qtip" title="La cuenta del usuario est&aacute; desactivada"{elseif $p.user_baneado == 1}class="qtip" title="La cuenta del usuario est&aacute; suspendida"{/if} class="title {if $p.post_private}categoria privado{/if}" alt="{$p.post_title}" title="{$p.post_title}" target="_self" href="{$tsConfig.url}/c/{$p.c_seo}/{$p.post_slug}">{$p.post_title|truncate:50}</a>
                        <span>{$p.post_date|hace} &raquo; <a href="{$tsConfig.url}/perfil/{$p.user_name}" class="hovercard" uid="{$p.post_user}"><strong>@{$p.user_name}</strong></a> &middot; Puntos <strong>{$p.post_puntos}</strong> &middot; Comentarios <strong>{$p.post_comments}</strong></span>
                        <span class="floatR"><a href="{$tsConfig.url}/posts/{$p.c_seo}/">{$p.c_nombre}</a></span>
                    </li>
                {/foreach}
            {else}
                <li class="emptyData">No hay posts aqu&iacute;</li>
            {/if}
        </ul>
        <br clear="left"/>
    </div>
    <div class="seccion-paginacion">
        {if $tsPosts.tipo == 'ver_mas'}
            <!-- BOTÓN VER MÁS -->
            {if $tsPosts.hasMore}
                <button class="btn-ver-mas" data-cursor="{$tsPosts.nextCursor}">Ver más publicaciones</button>
            {/if}
        {else}

            <div class="paginas-numericas" style="display: flex;justify-content: space-between; align-items: center;">
               {if $tsPostsPages.prev > 0}
                    <a href="{$tsConfig.url}/page/{$tsPostsPages.prev}" class="paginas-numericas">
                        Anterior
                    </a>
                {/if}
                <span>Página {$tsPosts.current} de {$tsPosts.pages}</span>
                {if $tsPostsPages.next > 0}
                    <a href="{$tsConfig.url}/page/{$tsPostsPages.next}" class="paginas-numericas">
                        Siguiente
                    </a>
                {/if}
            </div>

        {/if}
    </div>
</div>