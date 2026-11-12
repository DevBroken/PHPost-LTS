            </section>
        </main>
        <footer class="container flex justify-center items-center flex-col mx-auto my-4 border border-t border-subtle">
            <div class="footer-links">
                <div class="flex justify-center items-center gap-2">
                    <a href="{$tsConfig.url}/pages/ayuda/" class="font-semibold text-link text-link:hover text-sm text-decoration-none" title="Página de Ayuda">Ayuda</a>
                    <a href="{$tsConfig.url}/pages/chat/" class="font-semibold text-link text-link:hover text-sm text-decoration-none" title="Página de Chat">Chat</a>
                    <a href="{$tsConfig.url}/pages/contacto/" class="font-semibold text-link text-link:hover text-sm text-decoration-none" title="Página de Contacto">Contacto</a>
                    <a href="{$tsConfig.url}/pages/protocolo/" class="font-semibold text-link text-link:hover text-sm text-decoration-none" title="Página de Protocolo">Protocolo</a>
                </div>
                <div class="flex justify-center items-center gap-2">
                    <a href="{$tsConfig.url}/pages/terminos-y-condiciones/" class="font-semibold text-link text-link:hover text-sm text-decoration-none" title="Página de Términos y condiciones">Términos y condiciones</a>
                    <a href="{$tsConfig.url}/pages/privacidad/" class="font-semibold text-link text-link:hover text-sm text-decoration-none" title="Página de Privacidad de datos">Privacidad de datos</a>
                    <a href="{$tsConfig.url}/pages/dmca/" class="font-semibold text-link text-link:hover text-sm text-decoration-none" title="Página de Report Abuse - DMCA">Report Abuse - DMCA</a>
                </div>
            </div>
            <div class="copyright">
                <a href="{$tsConfig.url}" title="Sitio {$tsConfig.titulo}" rel="internal">{$tsConfig.titulo}</a> &copy; {$smarty.now|date_format:"%Y"} - Powered by <a href="https://github.com/DevBroken/PHPost-LTS" title="Repositorio DevBroken" rel="noopener" target="_blank">PHPost LTS</a>
            </div>
        </footer>
    </div>

    {**
     * El siguiente contenedor sirve para validar el Copyright
     * El ID del div NO debe ser alterado de lo contrario nuestro validador
     * tomará al sitio como una web sin copyright
    **}
    <template>
        [NAME] PHPost by Isidro
        [ORIGINAL] https://github.com/isidromlc/PHPost
    </template>

    {if $tsUser->is_admod && $tsConfig.c_see_mod && $tsConfig.novemods.total}
        <div id="stickymsg" onmouseover="$('#brandday').css('opacity',0.5);" onmouseout="$('#brandday').css('opacity',1);" onclick="location.href = '{$tsConfig.url}/moderacion/'" style="cursor:default;">Hay {$tsConfig.novemods.total} contenido{if $tsConfig.novemods.total != 1}s{/if} esperando revisi&oacute;n</div>
    {/if}
</body>
</html>