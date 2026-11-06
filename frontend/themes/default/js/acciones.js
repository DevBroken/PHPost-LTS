'use strict';

const ir_a_categoria = (categoria) => {
	let redirect = `${global_data.url}`;
	if(categoria === 'posts') {
		redirect += '/posts/';
	} else if(categoria !== 'base' || categoria !== 'posts') {
		redirect += `/c/${categoria}`;
	}
	location.href = redirect;
}

const PARAM_KEY_MAP = Object.freeze({
	userid: 'user_key',
	postid: 'postid',
	fotoid: 'fotoid',
	temaid: 'temaid'
});

function getParam(key, withoutAmp = true) {
	const realKey = PARAM_KEY_MAP[key];
	if (!realKey) return '';
	//
	const value = global_data?.[realKey];
	if (value == null || value === '') return '';
	//
	const prefix = withoutAmp ? '&' : '';
	return `${prefix}${realKey}=${encodeURIComponent(value)}`;
}

/**
 * Funcion para bloquear usuarios
*/
const bloquear = (user, bloqueado, lugar, aceptar) => {
	if(!aceptar && bloqueado) {
		mydialog.fast({
			title: 'Bloquear usuario',
			body: '&iquest;Realmente deseas bloquear a este usuario?',
			button: { text: 'Sí, Bloquear', action: () => bloquear(`'${user}', true, '${lugar}', true`) }
		});
		return;
	}
	// Cargamos
	if(bloqueado) mydialog.procesando_inicio('Procesando...');

	const data = {
	    user: user,
	    bloquear: bloqueado ? 1 : undefined,
	    user_key: global_data?.user_key
	};
	$.post('/api/bloqueos-cambiar', data, response => {
		const { codigo, mensaje } = response(response);
		mydialog.alert('Bloquear Usuarios', mensaje);
		if(codigo === 1) {
			//
			const texto = bloqueado ? 'Desbloquear' : 'Bloquear';
			const accionClase = bloqueado ? 'addClass' : 'removeClass';
			const accionClaseInversa = bloqueado ? 'removeClass' : 'addClass';
			const hrefUrl = `javascript:bloquear('${user}', ${!bloqueado}, '${lugar}')`;
			//
			switch (lugar) {
			    case 'perfil':
			        $('#bloquear_cambiar').html(texto)[accionClaseInversa]('bloquearU')[accionClase]('desbloquearU').attr('href', hrefUrl);
			    break;
			    case 'mensajes':
			        $('#bloquear_cambiar').html(texto).attr('href', hrefUrl);
			    break;
			    case 'mis_bloqueados':
			        $(`.bloquear_usuario_${user}`).attr('title', `${texto} Usuario`).html(texto)[accionClaseInversa]('bloqueadosU')[accionClase]('desbloqueadosU').attr('href', hrefUrl);
			    break;
			    case 'respuestas':
			    case 'comentarios':
			        $(`li.desbloquear_${user}`).toggle(bloqueado);
			        $(`li.bloquear_${user}`).toggle(!bloqueado);
			    break;
			}
		}
		loading.end(500);
	}).fail(() => {
		mydialog.error_500(`bloquear('${user}', ${bloqueado}, '${lugar}', true)`);
	}).always(() => mydialog.procesando_fin())
}

/* Notificaciones */
const notifica = {
	cache: {},
	retry: [],
	_parseResponse(response) {
		const params = response.split('-');
		const len = params.length;
		if (len === 2 || len === 4) {
			let isTwo = (len === 2);
			mydialog.alert('Notificaciones', params[(isTwo ? 1 : 3)]);
			return isTwo ? { esSpam: true } : null;
		}
		if (len === 3 && params[0] === '0') {
			return { fid: params[1], cantidad: params[2], raw: response, esSpam: false };
		}
		return null;
	},
	userInMencionHandle(response) {
		const data = this._parseResponse(response);
		if (!data) return; // Si fue error o inválido, salimos
		// Notificación exitosa (formato: "0-fid-cantidad")
		const { fid, cantidad } = data;
		$(`a.mf_${fid}`).toggle();
		$(`.mft_${fid}`).html(number_format(cantidad));
		vcard_cache[`mf_${fid}`] = '';
	},
	userMenuHandle(response) {
		const data = this._parseResponse(response);
		if (!data) return;
		const { fid } = data;
		this.cache[`following_${fid}`] = 0;
		$('div.avatar-box').children('ul').hide();
	},
	userInPostHandle(response) {
		const data = this._parseResponse(response);
		if (!data) return;
		const { cantidad, raw } = data;
		$('a.follow_user_post, a.unfollow_user_post').toggle();
		$('div.metadata-usuario > span.nData.user_follow_count').html(number_format(cantidad));
		// Pasamos la respuesta original al siguiente handler
		this.userMenuHandle(raw);
	},
	userInMonitorHandle(response, obj) {
		if (!this._parseResponse(response)) return;
		$(obj).fadeOut(() => $(obj).remove());
	},
	inPostHandle(response) {
		const data = this._parseResponse(response);
		if (!data) return;
		$('a.follow_post, a.unfollow_post').parent().toggle();
		$('ul.post-estadisticas > li > span.icons.monitor').html(number_format(data.cantidad));
	},
	inComunidadHandle(response) {
		const data = this._parseResponse(response);
		if (!data) return;
		$('.follow_comunidad, .unfollow_comunidad').toggle();
		$('li.comunidad_seguidores').html(`${number_format(data.cantidad)} Seguidores`);
	},
	temaInComunidadHandle(response) {
		const data = this._parseResponse(response);
		if (!data) return;
		$('.followBox .follow_tema, .unfollow_tema').toggle();
		$('span.tema_notifica_count').html(`${number_format(data.cantidad)} Seguidores`);
	},
	ruserInAdminHandle(response) {
		const data = this._parseResponse(response);
		if (!data) return;
		$(`.ruser${data.fid}`).toggle();
	},
	listInAdminHandle(response) {
		const data = this._parseResponse(response);
		if (!data) return;
		const targetClase = `.list${data.fid}`;
		const $elementos = $(targetClase);
		// Alternamos la visibilidad de los elementos
		$elementos.toggle();
		// Optimizamos la lógica de opacidad de los contenedores padres
		const $primerElemento = $elementos.first();
		const estaOculto = $primerElemento.css('display') === 'none';
		const opacidad = estaOculto ? 0.5 : 1;
		// Reemplazamos la cadena de .parent() por .closest() que es mucho más seguro y rápido
		$primerElemento.closest('li').children('div').first().fadeTo(0, opacidad);
	},
	spanHandler(response) {
		const data = this._parseResponse(response);
		if (!data || !data.esSpam) {
			mydialog.close();
		}
	},
	spamPostHandle(response) {
		this.spanHandler(response);
	},
	spamTemaHandle(response) {
		this.spanHandler(response);
	},
	ajax(param, cb, obj) {
		const $target = $(obj);
		if ($target.hasClass('spinner')) return;
		// Guardamos el reintento de forma limpia como un objeto estructurado
		this.retry.push({ param, cb, obj });
		// Mostramos error
		const mostrarError = param[0] !== 'action=count';
		$target.addClass('spinner');
		loading.start();
		// Unificamos la finalización (loading.end) con .always() para evitar duplicar código
		$.post('/api/notificaciones-ajax', param.join('&') + getParam('key'), response => {
			$target.removeClass('spinner');
			cb(response, obj);
		}).fail(() => {
			$target.removeClass('spinner');
			if (mostrarError) mydialog.error_500(() => this.ajax(param, cb, obj));
		})
		.always(() => loading.end());
	},
	follow(type, id, cb, obj) {
		this.ajax([`action=follow`, `type=${type}`, `obj=${id}`], cb, obj);
	},
	unfollow(type, id, cb, obj) {
		this.ajax([`action=unfollow`, `type=${type}`, `obj=${id}`], cb, obj);
	},
	spam(id, cb) {
		this.ajax([`action=spam`, `postid=${id}`], cb);
	},
	c_spam(id, cb) {
		this.ajax([`action=c_spam`, `temaid=${id}`], cb);
	},
	shared(id, type, fn) {
		mydialog.fast({
			title: 'Recomendar',
			body: `¿Quieres recomendar este ${type} a tus seguidores?`,
			button: { text: 'Recomendar', action: () => `notifica.spam(${id}, ${fn})` }
		});
	},
	sharePost(id) {
		this.shared(id, 'post', 'notifica.spamPostHandle');
	},
	shareTema(id) {
		this.shared(id, 'tema', 'notifica.spamTemaHandle');
	},
	last: function () {
		var c = parseInt($('#alerta_mon > a > span').html());
			mensaje.close();
		if ($('#mon_list').css('display') != 'none') {
			$('#mon_list').fadeOut();
			$('a[name=Monitor]').parent('li').removeClass('monitor-notificaciones');
		} else {
			if (($('#mon_list').css('display') == 'none' && c > 0) || typeof notifica.cache.last == 'undefined') {
				$('a[name=Monitor]').children('span').addClass('spinner');
				$('a[name=Monitor]').parent('li').addClass('monitor-notificaciones');
				$('#mon_list').slideDown();
				notifica.ajax(Array('action=last'), function (r) {
					notifica.cache['last'] = r;
					notifica.show();
				});
			} else notifica.show();
		}
	},
	check() {
		this.ajax(['action=count'], notifica.popup);
	},
	popup: function (r) {
		var c = parseInt($('#alerta_mon > a > span').html());
		if (r != c && r > 0) {
			if (r != 1) var not_total = ' notificaciones'; else var not_total = ' notificaci&oacute;n';
			if (!$('#alerta_mon').length) $('div.userInfoLogin > ul > li.monitor').append('<div class="alertas" id="alerta_mon"><a title="' + r + not_total + '"><span></span></a></div>');
			$('#alerta_mon > a > span').html(r);
			$('#alerta_mon').animate({ top: '-=5px' }, 100, null, function(){ $('#alerta_mon').animate({ top: '+=5px' }, 100) });
		} else if (r == 0) $('#alerta_mon').remove();
	},
	show: function () {
		if (typeof notifica.cache.last != 'undefined') {
			$('#alerta_mon').remove();
			$('a[name=Monitor]').parent('li').addClass('monitor-notificaciones');
			$('a[name=Monitor]').children('span').removeClass('spinner');
			$('#mon_list').show().children('ul').html(notifica.cache.last);
		}
	},
	filter: function (x, obj) {
		$.ajax({
			url: global_data.url + '/notificaciones-filtro.php',
			type: 'post',
			data: 'fid=' + x
		});
		var v = $(obj).prop('checked') ? 1 : 0;
	},
	close: function(){
		$('#mon_list').hide();
		$('a[name=Monitor]').parent('li').removeClass('monitor-notificaciones');
	}
}
/* Mensajes */
var mensaje = {
	cache: {},
	vars: [],
	// CREAR HTML
	form: function (){
		var html = '';
		if(this.vars['error']) html += '<div class="emptyData">' + this.vars['error'] + '</div><br style="clear:both">'
		html += '<div class="m-col1">Para:</div>'
		html += '<div class="m-col2"><input type="text" value="' + this.vars['to'] + '" maxlength="16" tabindex="0" size="20" id="msg_to" name="msg_to"/> <span style="font-size: 10px;">(Ingrese el nombre de usuario)</span></div><br style="clear:both" />'
		html += '<div class="m-col1">Asunto:</div>'
		html += '<div class="m-col2"><input type="text" value="' + this.vars['sub'] + '" maxlength="100" tabindex="0" size="50" id="msg_subject" name="msg_subject"/></div><br /><br style="clear:both"/>'
		html += '<div class="m-col1">Mensaje:</div>'
		html += '<div class="m-col2"><textarea tabindex="0" rows="10" id="msg_body" name="msg_body" style="height:100px; width:350px">' + this.vars['msg'] + '</textarea></div><br style="clear:both"/>'
		return html;
	},
	// FUNCIONES AUX
	checkform: function (h){
		if(parseInt(h) == 0)
			mensaje.enviar(1);
		else if(parseInt(h) == 1) {
			mensaje.nuevo(mensaje.vars['to'], mensaje.vars['sub'], mensaje.vars['msg'], 'No es posible enviarse mensajes a s&iacute; mismo.');
		} else if(parseInt(h) == 2) {
			mensaje.nuevo(mensaje.vars['to'], mensaje.vars['sub'], mensaje.vars['msg'], 'Este usuario no existe. Por favor, verif&iacute;calo.');
		}
	},
	alert: function(h){
		mydialog.procesando_fin();
		mydialog.alert('Aviso','<div class="emptyData">' + h + '</div>');
	},
	mostrar: function(show, obj){
		//
		$('.GBTabset a').removeClass('here');
		//
		if(show == 'all'){
			$('#mensajes div').show();
			$(obj).addClass('here');
		} else if(show == 'unread'){
			$('#mensajes div.GBThreadRow').hide();
			$('#mensajes table.unread').parent().show();
			$(obj).addClass('here');
		}
	},
	select: function(act){
		var inputs = $('#mensajes .GBThreadRow :input');
		inputs.each(function() {
			if(act == 'all'){
				$(this).attr({checked: 'checked'});
			} else if(act == 'read'){
				if($(this).attr('class') != 'inread'){
					$(this).attr({checked: 'checked'});
				} else $(this).attr({checked: ''});
			} else if(act == 'unread'){
				if($(this).attr('class') == 'inread'){
					$(this).attr({checked: 'checked'});
				} else $(this).attr({checked: ''});
			} else if(act == 'none'){
				$(this).attr({checked: ''});
			}
		});
	},
	modificar: function(act){
		var inputs = $('#mensajes .GBThreadRow :input');
		var ids = new Array();
		var i = 0;
		//
		inputs.each(function(){
			var este = $(this).prop('checked');
			//
			if(este != false){
				// AGREGAR EL ID
				ids[i] = $(this).val();
				i++;
				// PARA LOS ESTILOS
				var cid = $(this).val().split(':');
				// MARCAR LEIDO
				if(act == 'read'){
					$('#' + cid[0]).removeClass('unread');
					$(this).removeClass('inread');
				// MARCAR NO LEIDO
				} else if(act == 'unread'){
					$('#' + cid[0]).addClass('unread');
					$(this).addClass('inread');
				// ELIMINAR
				} else if(act == 'delete') $('#' + cid[0]).parent().remove();
			}
		});
		// ENVIAR CAMBIOS
		if(ids.length > 0){
			var params = ids.join(',');
			mensaje.ajax('editar','ids=' + params + '&act=' + act, function(r){});
		}
	},
	eliminar: function(id,type){
		mensaje.ajax('editar','ids=' + id + '&act=delete',function(r){
			if(type == 1) {
				var cid = id.split(':');
				$('#mp_' + cid[0]).remove();
			} else if(type == 2) {
				 location.href = global_data.url + '/mensajes/';
			}
		});
	},
	marcar: function(id, a, type, obj){
		var act = (a == 0) ? 'read' : 'unread';
		var show = (act == 'read') ? 'unread' : 'read';
		//
		mensaje.ajax('editar','ids=' + id + '&act=' + act,function(r){
			// CAMBIAR ENTRE LEIDO Y NO LEIDO
			if(type == 1){
				var cid = id.split(':');
				if(act == 'read')
				$('#mp_' + cid[0]).removeClass('unread');
				else
				$('#mp_' + cid[0]).addClass('unread');
				//
				$(obj).parent().find('a').hide();
				$(obj).parent().find('.' + show).show();
			} else {
				location.href = global_data.url + '/mensajes/';
			}
		});
	},
	// POST
	ajax: function(action, params, fn){
		$('#loading').fadeIn(250);
		$.ajax({
			type: 'POST',
			url: global_data.url + '/mensajes-' + action + '.php',
			data: params,
			success: function(h){
				fn(h);
				$('#loading').fadeOut(350);
			}
		});
	},
	// PREPARAR EL ENVIO
	nuevo: function (para, asunto, body, error){
		// GUARDAR
		this.vars['to'] = para;
		this.vars['sub'] = asunto;
		this.vars['msg'] = body;
		this.vars['error'] = error;
		//
		mydialog.procesando_fin();
		mydialog.show(true);
		mydialog.title('Nuevo mensaje');
		mydialog.body(this.form());
		mydialog.buttons(true, true, 'Enviar', '$(\'.wysibb-texarea\').sync(); mensaje.enviar(0)', true, true, true, 'Cancelar', 'close', true, false);
		$('#msg_body').wysibb({
			resize_maxheight: 170,
			buttons: "smilebox,|,bold,italic,underline,strike,sup,sub,|,img,video,link,|,removeFormat"
		});
		mydialog.center();
	},
	// ENVIAR...
	enviar: function (enviar){
		// DATOS
		this.vars['to'] = $('#msg_to').val();
		this.vars['sub'] = encodeURIComponent($('#msg_subject').val());
		this.vars['msg'] = encodeURIComponent($('#msg_body').bbcode());
		// COMPROBAR
		if(enviar == 0){ // VERIFICAR...
			if(this.vars['to'] == '')
				mensaje.nuevo(mensaje.vars['to'], mensaje.vars['sub'], mensaje.vars['msg'], 'Por favor, especific&aacute; el destinatario.');
			if(this.vars['msg'] == '')
				mensaje.nuevo(mensaje.vars['to'], mensaje.vars['sub'], mensaje.vars['msg'], 'El mensaje esta vac&iacute;o.');
			//
			mydialog.procesando_inicio('Verificando...', 'Nuevo Mensaje');
			this.ajax('validar', 'para=' + this.vars['to'], mensaje.checkform);
		} else if(enviar == 1){
			mydialog.procesando_inicio('Enviando...', 'Nuevo Mensaje');
			// ENVIAR
			this.ajax('enviar', 'para=' + mensaje.vars['to'] + '&asunto=' + mensaje.vars['sub'] + '&mensaje=' + mensaje.vars['msg'], mensaje.alert);
		}
	},
	// RESPONDER
	responder: function(mp_id){
		this.vars['mp_id'] = $('#mp_id').val();
		this.vars['mp_body'] = encodeURIComponent($('#respuesta').bbcode()); // Fix: 06/05/2018 - 1.3
		if(this.vars['mp_body'] == '') {
			$('#respuesta').focus();
			return;
		}
		//
		this.ajax('respuesta','id=' + this.vars['mp_id'] + '&body=' + this.vars['mp_body'], function(h){
			$('#respuesta').val(''); // LIMPIAMOS
			$('.wysibb-body').html('');
			switch(h.charAt(0)){
				case '0':
					mydialog.alert("Error", h.substring(3));
				break;
				case '1':
					$('#historial').append($(h.substring(3)).fadeIn('slow'));
				break;
			}
			$('#respuesta').focus();
		});
	},
	last: function () {
		var c = parseInt($('#alerta_mps > a > span').html());
		notifica.close();
			//
		if ($('#mp_list').css('display') != 'none') {
			$('#mp_list').hide();
			$('a[name=Mensajes]').parent('li').removeClass('monitor-notificaciones');
		} else {
			if (($('#mp_list').css('display') == 'none' && c > 0) || typeof mensaje.cache.last == 'undefined') {
				$('a[name=Mensajes]').children('span').addClass('spinner');
				$('a[name=Mensajes]').parent('li').addClass('monitor-notificaciones');
				$('#mp_list').show();
				mensaje.ajax('lista', '', function (r) {
					mensaje.cache['last'] = r;
					mensaje.show();
				});
			} else mensaje.show();
		}
	},
	popup: function (mps) {
		var c = parseInt($('#alerta_mps > a > span').html());
		if (mps != c && mps > 0) {
				if (mps != 1) var mps_total = ' mensajes'; else var mps_total = ' mensaje';
			if (!$('#alerta_mps').length) $('div.userInfoLogin > ul > li.mensajes').append('<div class="alertas" id="alerta_mps"><a title="' + mps + mps_total + '"><span></span></a></div>');
			$('#alerta_mps > a > span').html(mps);
			$('#alerta_mps').animate({ top: '-=5px' }, 100, null, function(){ $('#alerta_mps').animate({ top: '+=5px' }, 100) });
		} else if (mps == 0) $('#alerta_mps').remove();
	},
	show: function () {
		if (typeof mensaje.cache.last != 'undefined') {
			$('#alerta_mps').remove();
			$('a[name=Mensajes]').parent('li').addClass('monitor-notificaciones');
			$('a[name=Mensajes]').children('span').removeClass('spinner');
			$('#mp_list').show().children('ul').html(mensaje.cache.last);
		}
	},
	close: function(){
		$('#mp_list').slideUp();
		$('a[name=Mensajes]').parent('li').removeClass('monitor-notificaciones');
	}
}


/* IMAGENES */
var imagenes = {
	total: 0,
	move: '-250px',
	presentacion: function(){
		$('#imContent').animate({top: '0px'}, 1000, () => {
			$('#imContent').css({top: imagenes.move})
			for(var i = imagenes.total; i >= 0; i--) $('#img_' + i).html($('#img_' + (i - 1)).html());
			//
			$('#img_0').html($('#img_' + imagenes.total).html());
			// INFINITO :D
			setTimeout(() => imagenes.presentacion(), 5000);
		});
	}
}

// NEWS
var news = {
	total: 0,
	count: 1,
	slider: function(){
		if(news.total > 1){
			if(news.count < news.total) news.count++;
			else news.count = 1;
			//
			$('#top_news > li').hide();
			$('#new_' + news.count).fadeIn();
			// INFINITO :D
			setTimeout("news.slider()",7000);
		}
	}
}

// READY
$(document).ready(() => {
	$('body').on('click', e => {
		if ($('#mon_list').css('display') != 'none' && $(e.target).closest('#mon_list').length == 0 && $(e.target).closest('a[name=Monitor]').length == 0) notifica.last();
		if ($('#mp_list').css('display') != 'none' && $(e.target).closest('#mp_list').length == 0 && $(e.target).closest('a[name=Mensajes]').length == 0) mensaje.last();
	});

	/* NOTICIAS */
	news.total = $('#top_news > li').length;
	news.slider();
	/* IMAGENES */
	imagenes.presentacion();

	if(global_data.notifica > 0) {
		notifica.popup(global_data.notifica);
	}
	if(global_data.mensaje > 0 && global_data.action !== 'leer') {
		mensaje.popup(global_data.mensaje);
	}

});