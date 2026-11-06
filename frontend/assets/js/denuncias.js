/* Denuncias */
var denuncia = {
    nueva: function(type, obj_id, obj_title, obj_user){
        // PLANTILLA
        var params = ['obj_id=' + obj_id, 'obj_title=' + obj_title, 'obj_user=' + obj_user].join('&')
        $('#loading').fadeIn(250);
        $.post(global_data.url + '/denuncia-' + type + '.php', params, h => {
            denuncia.set_dialog(h, obj_id, type);
            $('#loading').fadeOut(350);
        });
    },
    set_dialog: function(html, obj_id, type){
        // MYDIALOG
        mydialog.mask_close = false;
        mydialog.close_button = true;
        mydialog.show();
        mydialog.title('Denunciar ' + type);
        mydialog.body(html);
        mydialog.buttons(true, true, 'Enviar', "denuncia.enviar(" + obj_id + ", '" + type + "')", true, true, true, 'Cancelar', 'close', true, false);
        mydialog.center();
    },
    enviar: function(obj_id, type){
        var params = [
            'obj_id=' + obj_id,
            'razon=' + $('select[name=razon]').val(),
            'extras=' + $('textarea[name=extras]').val()
        ].join('&');
        //
        $('#loading').fadeIn(250);
        $.post(global_data.url + '/denuncia-' + type + '.php', params, h => {
            mydialog.alert((h.charAt(0) === '0' ? "Error" : "Bien"),'<div class="emptyData">' + h.substring(3) +  '</div>');
            $('#loading').fadeOut(350);
        });
    }
}