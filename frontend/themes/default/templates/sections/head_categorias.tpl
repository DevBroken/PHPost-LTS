<div class="floatR filterCat">
    <span>Filtrar por Categorías:</span>
    <select onchange="ir_a_categoria(this.value)" style="margin:-2px 0 0;">
        <option selected="selected" value="root">Seleccionar categoría</option>
        <option value="{if $tsAllows.c_allow_portal == 0}base{else}posts{/if}">Ver Todas</option>
		{foreach from=$tsCategorias item=c}
            <option value="{$c.c_seo}"{if $tsCategoria == '$c.c_seo'} selected{/if}>{$c.c_nombre}</option>
        {/foreach}
    </select>
 </div>