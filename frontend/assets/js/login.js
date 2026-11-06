'use strict';

const $button_submit = $('button[type=submit]');
const $form = $('form[name="form_login_user"]');
const $actions = $('span[role=button]');
const template_use = `<div class="form-group">
    <label class="form-label" for="email">Email</label>
    <input class="form-input" type="email" id="email" name="email" placeholder="jhondoe@example.test" required>
    <small class="form-helper" data-target="email"></small>
</div>`;

/**
 * Cambiar estado del boton
 */
const button_status = (text, disabled = false) => $button_submit.prop('disabled', disabled).text(text);

/**
 * Valida un input individual usando las propiedades de HTML5
 */
const validar = (event) => {
    const input = event.target;
    const $helper = $(`small[data-target=${input.name}]`);
    input.setCustomValidity("");

    if (input.validity.valueMissing) {
        input.setCustomValidity("Este campo es obligatorio.");
    } else if (input.validity.typeMismatch && input.type === 'email') {
        input.setCustomValidity("Por favor, introduce un correo electrónico válido.");
    }

    if ($helper.length) {
        $helper.text(input.validationMessage);
    }
};

// Vincular validación en tiempo real a los inputs existentes en el formulario
const inputs = [].slice.call($form.find('input:not([type=hidden])'));
inputs.forEach(input => {
    $(input).on('input blur', validar);
});

/**
 * Iniciamos la sesión si todo es correcto
 */
const start_session_user = params => {
    $.post('/api/login-user', params, (response) => {
        mytoast.ok(response.message);
        button_status('Redireccionando...', true);
        setTimeout(() => location.href = global_data.url, 1000);
    }, 'json')
    .fail((response) => {
        if (response.responseJSON) {
            mytoast.error(response.responseJSON.message, "Error en credenciales:");
        } else {
            mytoast.error("Error de conexión o error fatal de PHP");
        }
        button_status('Iniciar sesión');
    });
}

/**
 * Comprobamos los campos
 */
const check_form_field = () => {
    const artscript = $form.find('input[name=artscript]').val();
    // Filtro antirobots Honeypot
    if (artscript && artscript.trim() !== '') {
        mytoast.error('Acceso denegado, no puedes continuar.');
        return false;
    }
    return true;
}

/**
 * Enviamos el formulario
 */
$button_submit.on('click', event => {
    event.preventDefault();
    if (!check_form_field()) {
        return;
    }
    if (!$form[0].checkValidity()) {
        mytoast.error('Debes completar el formulario de forma correcta.');
        return;
    }
    const form_data_send = $form.serialize();
    button_status('Cargando su cuenta...', true);
    start_session_user(form_data_send);
});

const action_use = (target, status = false) => {
    const ATTRIBUTES = {
        password: { title: 'Recuperar Contraseña' },
        validation: { title: 'Reenviar validación' }
    }
    if(!status) {
        mydialog.show({
            title: ATTRIBUTES[target].title,
            body: template_use,
            buttons: [
                { text: 'Continuar', variant: 'primary', action: () =>  action_use(target, true) },
                { text: 'Cancelar', variant: 'neutral', action: 'close', focus: true }
            ]
        });
        setTimeout(() => {
            const modalInput = document.getElementById('email');
            if (modalInput) {
                $(modalInput).on('input blur', validar);
            }
        }, 100);
        return;
    }
    const modalInputEmail = document.getElementById('email');
    if (modalInputEmail) {
        validar({ target: modalInputEmail });
        if (!modalInputEmail.checkValidity()) {
            mytoast.error(modalInputEmail.validationMessage || "El correo electrónico no es válido.");
            return;
        }
    }
    const email = {
        email: $('#email').val()
    };
    $.post(`/api/recover-${target}.php`, email, ({ status, message }) => {
        mydialog.show({
            type: status,
            title: (status === 'error' ? 'Opps!' : 'Hecho'),
            message: message
        });
    }, 'json');
}

/**
 * Manejamos las acciones de:
 * Recuperar contraseña
 * Enviar correo de validación
 */
$actions.on('click', ({ target }) => action_use($(target).data('action')));