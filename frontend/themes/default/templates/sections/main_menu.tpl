<nav class="px-4 flex justify-between items-center bg-raised h-56">
    <div class="flex justify-start items-center gap-4">
        <div class="brand flex justify-start items-center gap-3 border">
            <div class="menu-toggle none justify-center items-center w-32 h-32 rounded border border-default text-primary pointer transition-background delay-150 ease-in">
                {$Sources->svg('menu') nofilter}
            </div>
            <a href="{$tsConfig.url}" title="{$tsConfig.titulo} - {$tsConfig.slogan}" class="brand-logo flex items-center gap-2 font-bold text-primary">
                <span class="bg-primary block text-inverse w-32 h-32 flex justify-center items-center rounded-sm" aria-hidden="true">{$tsConfig.titulo|truncate:"2":""}</span>
                <span class="text-xl" style="letter-spacing: -1px">{$tsConfig.titulo}</span>
            </a>
        </div>
        <ul class="navbar-menu flex flex-row m-0 p-0 items-center gap-1 flex-wrap" id="js-navbar-menu" role="menubar">
            {if $tsAllows.c_allow_portal && $tsUser->is_member}
                <li class="menu-item flex items-center{if $tsPage == 'mi'} active{/if}" role="none">
                    <a href="{$tsConfig.url}/mi/" role="menuitem" title="Ir a Inicio">Portal</a>
                </li>
            {/if}
            <li class="menu-item flex relative items-center dropdown{if $tsPage == 'posts' || $tsPage == 'home'} active{/if}" aria-haspopup="true" aria-expanded="false">
                <a href="{$tsConfig.url}/posts/" class="dropdown-toggle" aria-haspopup="true" aria-controls="menu-posts">Posts <span class="arrow-down" aria-hidden="true"></span></a>
                <div class="dropdown-list" id="menu-posts" role="menu">
                    <a href="{$tsConfig.url}/{if $tsPage == 'home' || $tsPage == 'posts'}posts/{/if}" class="dropdown-item{if $tsPage == 'home'} dropdown-active{/if}" role="menuitem">Inicio</a>
                    <a href="{$tsConfig.url}/buscador/" class="dropdown-item{if $tsPage == 'buscador'} dropdown-active{/if}" role="menuitem">Buscador</a>
                    {if $tsUser->is_member}
                        {if $tsUser->is_admod || $tsUser->permisos.gopp}
                            <a href="{$tsConfig.url}/agregar/" class="dropdown-item{if $tsSubmenu == 'agregar'} dropdown-active{/if}" role="menuitem">Agregar Post</a>
                        {/if}
                        <a href="{$tsConfig.url}/mod-history/" class="dropdown-item{if $tsPage == 'mod-history'} dropdown-active{/if}" role="menuitem">Historial</a>
                        {if $tsUser->is_admod || $tsUser->permisos.moacp}
                            <a href="{$tsConfig.url}/moderacion/" class="dropdown-item{if $tsPage == 'moderacion'} dropdown-active{/if}" role="menuitem">Moderación</a>
                        {/if}
                    {/if}
                </div>
            </li>
            {if $tsConfig.c_fotos_private != '1' && $tsUser->is_member}
                <li class="menu-item dropdown{if $tsPage == 'fotos'} menu-active{/if}" aria-haspopup="true" aria-expanded="false">
                    <a href="{$tsConfig.url}/fotos/" class="dropdown-toggle" aria-haspopup="true" aria-controls="menu-fotos">Fotos <span class="arrow-down" aria-hidden="true"></span></a>
                    <div class="dropdown-list" id="menu-fotos" role="menu">
                        <a href="{$tsConfig.url}/fotos/" class="dropdown-item{if $tsAction == ''} dropdown-active{/if}" role="menuitem">Inicio</a>
                        {if $tsAction == 'album' && $tsFUser.0 != $tsUser->uid}
                            <a href="{$tsConfig.url}/fotos/{$tsFUser.1}" class="dropdown-item dropdown-active" role="menuitem">Álbum de {$tsFUser.1}</a>
                        {/if}
                        {if $tsUser->is_admod || $tsUser->permisos.gopf}
                            <a href="{$tsConfig.url}/fotos/agregar.php" class="dropdown-item{if $tsAction == 'agregar'} dropdown-active{/if}" role="menuitem">Agregar Foto</a>
                        {/if}
                        <a href="{$tsConfig.url}/fotos/{$tsUser->nick}" class="dropdown-item{if $tsAction == 'album' && $tsFUser.0 == $tsUser->uid} dropdown-active{/if}" role="menuitem">Mis Fotos</a>
                    </div>
                </li>
            {/if}
            <li class="menu-item dropdown{if $tsPage == 'tops'} menu-active{/if}" aria-haspopup="true" aria-expanded="false">
                <a href="{$tsConfig.url}/top/" class="dropdown-toggle" aria-haspopup="true" aria-controls="menu-tops">TOPs <span class="arrow-down" aria-hidden="true"></span></a>
                <div class="dropdown-list" id="menu-tops" role="menu">
                    <a href="{$tsConfig.url}/top/posts/" class="dropdown-item{if $tsAction == 'posts'} dropdown-active{/if}" role="menuitem">Posts</a>
                    <a href="{$tsConfig.url}/top/usuarios/" class="dropdown-item{if $tsAction == 'usuarios'} dropdown-active{/if}" role="menuitem">Usuarios</a>
                </div>
            </li>
            {if $tsUser->is_member && $tsUser->is_admod == 1}
                <li class="menu-item{if $tsPage == 'admin'} menu-active{/if}" role="none">
                    <a href="{$tsConfig.url}/admin/" role="menuitem">Administración</a>
                </li>
            {/if}
        </ul>
    </div>
    <!--  -->
    <div class="navbar-user-actions flex justify-end items-center gap-3">
        {if $tsUser->is_member}
            <div class="action-icon-wrapper dropdown monitor" aria-haspopup="true" aria-expanded="false">
                <a href="{$tsConfig.url}/monitor/" class="icon-btn" title="Monitor de usuario" name="Monitor" aria-controls="mon_list">
                    {$Sources->svg('bell') nofilter}
                </a>
                <div class="dropdown-list list-wide" id="mon_list" role="menu">
                    <div class="list-header">
                        <a href="{$tsConfig.url}/monitor/">Notificaciones</a>
                    </div>
                    <ul class="async-list-target" role="none"></ul>
                    <a href="{$tsConfig.url}/monitor/" class="ver-mas">Ver más notificaciones</a>
                </div>
            </div>
            <div class="action-icon-wrapper dropdown mensajes" aria-haspopup="true" aria-expanded="false">
                <a href="{$tsConfig.url}/mensajes/" class="icon-btn" title="Mensajes Personales" name="Mensajes" aria-controls="mp_list">
                    {$Sources->svg('mail') nofilter}
                </a>
                <div class="dropdown-list list-wide" id="mp_list" role="menu">
                    <div class="list-header">
                        <a href="{$tsConfig.url}/mensajes/">Mensajes</a>
                    </div>
                    <ul class="async-list-target" id="boxMail" role="none"></ul>
                    <a href="{$tsConfig.url}/mensajes/" class="ver-mas">Ver todos los mensajes</a>
                </div>
            </div>
            {if $tsAvisos}
                <div class="action-icon-wrapper alert-active" aria-label="Avisos pendientes">
                    <a title="Avisos" href="{$tsConfig.url}/mensajes/avisos/" class="icon-btn">
                        {$Sources->svg('bell-dot') nofilter}
                    </a>
                    <div id="alerta_avs" class="alertas-badge"><span>{$tsAvisos}</span></div>
                </div>
            {/if}
            <div class="action-icon-wrapper dropdown user-profile-dropdown" aria-haspopup="true" aria-expanded="false">
                <a href="{$tsConfig.url}/perfil/{$tsUser->nick}" class="dropdown-toggle username-link flex justify-end items-center gap-1" title="Mi Cuenta" aria-controls="menu-profile">
                    {$Sources->svg('user') nofilter}
                    <span class="username-text">{$tsUser->nick}</span>
                </a>
                <div class="dropdown-list open r-0" id="menu-profile" role="menu">
                    <div class="dropdown-item"><a href="{$tsConfig.url}/favoritos.php" role="menuitem">Mis Favoritos</a></div>
                    <div class="dropdown-item"><a href="{$tsConfig.url}/borradores.php" role="menuitem">Mis Borradores</a></div>
                    <div class="dropdown-item"><a href="{$tsConfig.url}/cuenta/" role="menuitem">Configuración Cuenta</a></div>
                    <div class="dropdown-item"><a href="{$tsConfig.url}/perfil/{$tsUser->info.user_name}" role="menuitem">Mi Perfil Público</a></div>
                    <hr class="dropdown-divider">
                    <div class="dropdown-item logout-item"><a href="{$tsConfig.url}/api/login-salir" role="menuitem">Cerrar sesión</a></div>
                </div>
            </div>
        {else}
            <div class="auth-anonymous">
                <a href="{$tsConfig.url}/auth/login" class="btn-login">Identificarme</a>
                <a href="{$tsConfig.url}/auth/register" class="btn-register">¡Registrate!</a>
            </div>
        {/if}
    </div>
</nav>