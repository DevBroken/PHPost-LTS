{include "main_header.tpl"}

				{include "m.top_sidebar.tpl"}
                {if $tsAction == 'posts'}
				{include "m.top_posts.tpl"}
                {elseif $tsAction == 'usuarios'}
                {include "m.top_users.tpl"}
                {/if}
                <div style="clear: both;"></div>
                
{include "main_footer.tpl"}