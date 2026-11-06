{include "main_header.tpl"}
				<script type="text/javascript" src="{$tsConfig.default}/js/perfil.js"></script>
                <script type="text/javascript" src="{$tsConfig.default}/js/portal.js"></script>
                <div id="left_box">
                    {include "m.portal_userbox.tpl"}
                    <br class="spacer"/>
                    {include "m.global_ads_160.tpl"}
                </div>
                <div id="center_box">
                    <div id="portal">
                        <div class="tabs_menu box_title">
                            <ul id="tabs_menu">
                                <li class="selected"><a onclick="portal.load_tab('news', this); return false" class="news">Noticias</a></li>
                                <li><a onclick="portal.load_tab('activity', this); return false;" class="activity">Actividad</a></li>
                                <li><a onclick="portal.load_tab('posts', this); return false;" class="posts">Posts</a></li>
                                <li><a onclick="portal.load_tab('favs', this); return false;" class="favs">Favoritos</a></li>
                            </ul>
                            <div class="clearBoth"></div>
                        </div>
                        <div id="portal_content">
                            {include "m.portal_noticias.tpl"}
                            {include "m.portal_activity.tpl"}
                            {include "m.portal_posts.tpl"}
                            {include "m.portal_posts_favoritos.tpl"}
                        </div>
                    </div>
                </div>
                <div id="right_box">
                    <br />
                    {include "m.home_stats.tpl"}
                    {include "m.portal_posts_visitados.tpl"}
                    {include "m.portal_fotos.tpl"}
                    {include "m.portal_afiliados.tpl"}
                    <!--Poner aqui mas modulos-->
                </div>
                <div style="clear:both"></div>

{include "main_footer.tpl"}