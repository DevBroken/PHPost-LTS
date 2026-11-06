'use strict';

const $button_submit = $('button[type=submit]');
const $actions = $('span[role=button]');
const $form = $('form[name="form_register_user"]');
const KEY_ID = $button_submit.data('sitekey');

const settings = {
    nickname: {
        regex: /^[a-zA-Z0-9\_\-]{4,20}$/,
        approved: false
    },
    password: {
        regex: /^.{4,32}$/,// /^(?=.*[a-z])(?=.*[A-Z])[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]{4,32}$/,
        approved: false
    },
    email: {
        regex: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/,
        approved: false
    }
}

const INPUTS = (type = '') => {
    const all = {
        artscript: $form.find('input[name=artscript]').val()?.trim() || '',
        email: $form.find('input[name=email]').val()?.trim() || '',
        nickname: $form.find('input[name=nickname]').val()?.trim() || '',
        password: $form.find('input[name=password]').val()?.trim() || '',
        password_confirm: $form.find('input[name=password_confirm]').val()?.trim() || '',
    }
    return (type !== '') ? all[type] : all;
}

/**
 * Comprobamos los campos
 */
const check_form_field = () => {
    const artscript = INPUTS('artscript');
    if (artscript && artscript !== '') {
        mytoast.error('Acceso denegado, no puedes continuar.');
        return false;
    }
    const { nickname, email, password, password_confirm } = INPUTS();

    // Verificar estos campos que no vengan vacios...
    if (!nickname || !email || !password || !password_confirm) {
        mytoast.error('Debes completar todos los campos del formulario.');
        return false;
    }
    if (password !== password_confirm) {
        mytoast.error('Las contraseñas no coinciden.');
        return false;
    }
    const termsChecked = $form.find('input[name=terms]').is(':checked');
    if (!termsChecked) {
        mytoast.error('Debes aceptar los términos y condiciones.');
        return false;
    }
    return true;
};

/**
 * Valida de forma asíncrona la disponibilidad de Nickname o Email
 * @param {string} name - Nombre del campo ('nickname' o 'email')
 */
const check_name_email = (name) => {
    const $input = $(`input[name=${name}]`);
    // Limpiamos espacios en blanco
    const value = $input.val().replace(/\s+/g, '');
    $input.val(value);
    // Uso el regex de settings
    if (!settings[name].regex.test(value)) {
        const message = (name === 'nickname') ? 'El usuario debe tener entre 4 y 20 caracteres (letras, números, _ o -).' : 'El formato del correo electrónico no es válido.';
        settings[name].approved = false;
        formValid(name, message, false);
        return;
    }

    formValid(name, 'Comprobando...', true);
    const data = { [name]: value };

    $.post(`/api/registro-check-${name}`, data, response => {
        settings[name].approved = true;
        formValid(name, response.message, true);
    }, 'json')
    .fail((xhr) => {
        settings[name].approved = false;
        const message = xhr.responseJSON?.message || 'Error al comprobar la disponibilidad.';
        formValid(name, message, false);
    });
}


/**
 * Valida las reglas de negocio y evalúa la fuerza de la contraseña
 * @param {string} name - Nombre del campo ('password' o 'password_confirm')
 */
const check_password = (name) => {
    const { nickname, password, password_confirm } = INPUTS();
    // Evaluación de requisitos y fuerza visual
    let strengthScore = 0;
    let reqLength = (password.length >= 4 && password.length <= 32);
    let reqUpper = (/[A-Z]/.test(password));
    let reqNumber = (/[0-9]/.test(password));
    let reqSpecial = (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password));
    //
    if (reqLength) strengthScore++;
    if (reqUpper) strengthScore++;
    if (reqNumber) strengthScore++;
    if (reqSpecial) strengthScore++;
    //
    $('#req-length').css('color', reqLength ? '#2ecc71' : '');
    $('#req-upper').css('color', reqUpper ? '#2ecc71' : '');
    $('#req-number').css('color', reqNumber ? '#2ecc71' : '');
    $('#req-special').css('color', reqSpecial ? '#2ecc71' : '');

    // Actualizar barra de fuerza según los puntos acumulados (Máximo 4)
    const $bar = $('#strength-bar');
    if (password.length === 0) {
        $bar.css({'width': '0%', 'background': '#e0e0e0'});
    } else if (strengthScore <= 1) {
        $bar.css({'width': '25%', 'background': '#e74c3c'}); // Rojo (Débil)
    } else if (strengthScore === 2) {
        $bar.css({'width': '50%', 'background': '#f1c40f'}); // Amarillo (Media)
    } else if (strengthScore === 3) {
        $bar.css({'width': '75%', 'background': '#3498db'}); // Azul (Buena)
    } else if (strengthScore === 4) {
        $bar.css({'width': '100%', 'background': '#2ecc71'}); // Verde (Segura)
    }
    // Bloqueos obligatorios
    if (nickname && nickname === password) {
        formValid('password', 'La contraseña no debe ser igual al nickname.', false);
        settings.password.approved = false;
        return;
    }
    // Verifica únicamente la longitud básica estipulada en tus settings originales
    if (!settings.password.regex.test(password)) {
        formValid('password', 'La contraseña debe tener entre 4 y 32 caracteres.', false);
        settings.password.approved = false;
        return;
    }
    // Validar coincidencia si el usuario ya escribió en el campo de confirmación
    if (password_confirm.length > 0) {
        if (password !== password_confirm) {
            formValid('password_confirm', 'Las contraseñas no coinciden.', false);
            settings.password.approved = false;
            return;
        } else {
            formValid('password_confirm', 'Las contraseñas coinciden.', true);
        }
    }
    // Si pasó los filtros obligatorios, la contraseña es válida independientemente de su fuerza
    settings.password.approved = true;
    formValid('password', '', true);
}

/**
 * Validar input
 */
const validate = ({ target: { name } }) => {
    const validadores = {
        nickname: () => check_name_email('nickname'),
        email: () => check_name_email('email'),
        password: () => check_password('password'),
        password_confirm: () => check_password('password_confirm')
    };
    if (validadores[name]) {
        formValid(name, '', true);
        validadores[name]();
    }
};

// Hacemos validaciones en tiempo real!
const inputs = [].slice.call($form.find('input:not([type=hidden], [type=checkbox])'));
inputs.forEach(input => $(input).on('input blur', validate));

// Desactivo el boton antes que nada!
$button_submit.prop('disabled', true).text('Cargando token...');

// Si enviamos el formulario
$button_submit.on('click', (event) => {
    event.preventDefault();
    inputs.forEach(input => validate({ target: input }));
    //
    if (!check_form_field()) {
        return false;
    }
    if (!$form[0].checkValidity()) {
        mytoast.error('Por favor, corrige los errores en el formulario antes de continuar.');
        return false;
    }
    const { nickname, email, password, password_confirm } = INPUTS();
    const payload = {
        nickname, email, password, password_confirm,
        terms: $form.find('input[name=terms]').is(':checked') ? '1' : '0',
        response: $('#response').val()
    };
    $.post('/api/registro-nuevo', payload, ({ message }) => {
        mytoast.ok(message);
        setTimeout(() => location.href = global_data.url, 2000);
    }, 'json')
    .fail((xhr) => {
        const errorMsg = xhr.responseJSON?.message || 'Error al procesar el registro.';
        mytoast.error(errorMsg);
    });
});

async function get_token() {
    try {
        const token = await grecaptcha.execute(KEY_ID, { action: 'submit' });
        $('#response').val(token);
        $button_submit.prop('disabled', false).text('Registrarse');
    } catch (error) {
        console.error("Error al obtener el token de reCAPTCHA:", error);
        mytoast.error(error);
    }
}
$(document).ready(() => {
    grecaptcha.ready(() => get_token());

    $('.password-show').on('click', function () {
        const $button = $(this);
        const $input = $button.parent().find('input');
        const $img = $button.find('img');
        // Estado actual
        const isPassword = $input.attr('type') === 'password';
        $input.attr('type', isPassword ? 'text' : 'password');
        // Cambiamos el icono
        const iconBase = `${global_data.url}/media/assets/images/icons`;
        $img.attr('src', `${iconBase}/${isPassword ? 'eye-closed.svg' : 'eye.svg'}`);
        // Opcional: Mejoramos la accesibilidad del botón en tiempo real
        $button.attr('title', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });


});