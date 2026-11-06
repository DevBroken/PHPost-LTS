/**
 * PHPost Global Utility Plugins (ES6+ Clean Edition, No Dependency jQuery)
 */

// # Empty: Verifica de forma estricta si un valor está vacío
const empty = (value) => {
    if (value === undefined || value === null || value === false || value === 0 || value === "" || value === "0") return true;
    if (typeof value === 'object') {
        return Object.keys(value).length === 0;
    }
    return false;
};

// # Htmlspecialchars_decode: Convierte entidades HTML de vuelta a caracteres normales
const htmlspecialchars_decode = (string, quoteStyle = 2) => {
    string = String(string).replace(/&lt;/g, '<').replace(/&gt;/g, '>');
    string = string.replace(/&amp;/g, '&');
    if (quoteStyle !== 0) { // ENT_NOQUOTES = 0
        string = string.replace(/&quot;/g, '"');
    }
    if (quoteStyle === 1 || quoteStyle === 3) { // ENT_QUOTES o ENT_HTML_QUOTE_SINGLE
        string = string.replace(/&#0*39;/g, "'");
    }
    return string;
};

// # Number_format: Formatea números con separadores de miles y decimales
const number_format = (number, decimals = 0, decPoint = '.', thousandsSep = ',') => {
    const n = !isFinite(+number) ? 0 : +number;
    const prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
    const toFixedFix = (n, prec) => {
        const k = Math.pow(10, prec);
        return '' + (Math.round(n * k) / k).toFixed(prec);
    };
    const s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, thousandsSep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(decPoint);
};

// # Rawurlencode: Codificación de URLs conforme a RFC 3986
const rawurlencode = (string) => {
    return encodeURIComponent(String(string))
        .replace(/!/g, '%21')
        .replace(/'/g, '%27')
        .replace(/\(/g, '%28')
        .replace(/\)/g, '%29')
        .replace(/\*/g, '%2A');
};

// # Base64_encode: Codificación Base64 nativa y segura (Soporta UTF-8)
const base64_encode = (string) => {
    if (typeof window !== 'undefined' && typeof window.btoa !== 'undefined') {
        return window.btoa(unescape(encodeURIComponent(String(string))));
    }
    return Buffer.from(String(string)).toString('base64');
};

/**
 * Loading - Navtive
 */
const loading = {
    _element: null,
    _create(type, duration) {
        if (!this._element) {
            this._element = document.getElementById('loading');
            // Opcional: Si no existe en el HTML, crea un div básico automáticamente
            if (!this._element) {
                this._element = document.createElement('div');
                this._element.id = 'loading';
                this._element.style.display = 'none';
                document.body.appendChild(this._element);
            }
        }
        if (type === 'init') {
            this._element.style.display = 'block';
            this._element.animate(
                [{ opacity: 0 }, { opacity: 1 }],
                { duration: duration, fill: 'forwards' }
            );
        } else if (type === 'finish') {
            this._element.animate(
                [{ opacity: 1 }, { opacity: 0 }],
                { duration: duration, fill: 'forwards' }
            ).onfinish = () => {
                this._element.style.display = 'none';
            };
        }
    },
    // Inicia el indicador de carga (fadeIn)
    start(duration = 250) {
        this._create('init', duration);
    },
    // Finaliza el indicador de carga (fadeOut)
    end(duration = 250) {
        this._create('finish', duration);
    }
};

// Inicia: loading.start();
// Termina: loading.end(500);
const response = message => {
    const [codigoStr, mensaje] = message.split(/:(.*)/s);
    return {
        code: parseInt(codigoStr, 10),
        message: mensaje.trim()
    };
}

/**
 * mydialog - Native HTML5 Component para PHPost
 * Totalmente desacoplado de jQuery, responsivo y adaptado a tokens.css
 */
const mydialog = {
    _element: null,

    _create() {
        if (document.getElementById('mydialog-native')) return;

        if (!document.getElementById('mydialog-styles')) {
            const style = document.createElement('style');
            style.id = 'mydialog-styles';
            style.textContent = `
                #mydialog-native { padding: 0; margin: auto; border: none; background: transparent; overflow: visible; }
                #mydialog-native::backdrop { background-color: var(--color-surface-sunken); opacity: 0.4; backdrop-filter: blur(8px); }
                #mydialog-native[open] { display: grid; place-items: center; }
                .dialog-loading-spinner { width: 20px; height: 20px; border: 2px solid var(--color-border-subtle); border-top-color: var(--color-focus-outline); border-radius: 50%; animation: dSpin 0.5s linear infinite; margin: 0.5rem auto; }
                @keyframes dSpin { to { transform: rotate(360deg); } }
            `;
            document.head.appendChild(style);
        }

        const dialog = document.createElement('dialog');
        dialog.id = 'mydialog-native';
        dialog.innerHTML = `
            <div class="modal">
                <div class="modal-header" id="md-title"></div>
                <div class="modal-body" id="md-body"></div>
                <div id="md-loading" style="display: none;"><div class="dialog-loading-spinner"></div></div>
                <div class="modal-footer" id="md-footer"></div>
            </div>
        `;

        document.body.appendChild(dialog);
        this._element = dialog;
    },

    show({ title = '', body = '', buttons = [], classAux = '', maskClose = true } = {}) {
        this._create();

        const modalContainer = this._element.querySelector('.modal');
        const titleNode = document.getElementById('md-title');
        const bodyNode = document.getElementById('md-body');
        const footerNode = document.getElementById('md-footer');
        const loadingNode = document.getElementById('md-loading');

        modalContainer.className = `modal ${classAux}`.trim();
        loadingNode.style.display = 'none';
        bodyNode.style.display = 'block';

        titleNode.innerHTML = title;
        bodyNode.innerHTML = typeof body === 'string' ? body : '';
        if (typeof body === 'object') {
            bodyNode.innerHTML = '';
            bodyNode.appendChild(body);
        }

        footerNode.innerHTML = '';
        if (buttons && buttons.length > 0) {
            footerNode.style.display = 'flex';
            buttons.forEach(btn => {
                const button = document.createElement('button');
                button.textContent = btn.text || 'Aceptar';
                button.dataset.variant = btn.variant || 'neutral';
                if (btn.disabled) button.disabled = true;

                button.addEventListener('click', () => {
                    if (btn.action === 'close') this.close();
                    else if (typeof btn.action === 'function') btn.action(this);
                });
                footerNode.appendChild(button);
                if (btn.focus) setTimeout(() => button.focus(), 50);
            });
        } else {
            footerNode.style.display = 'none';
        }

        this._element.onclick = (e) => {
            if (maskClose && e.target === this._element) this.close();
        };

        this._element.showModal();
    },

    close() {
        if (this._element) this._element.close();
    },

    alert(title, body, onAccept = 'close') {
        this.show({
            title,
            body,
            buttons: [{
                text: 'Entendido',
                variant: 'primary',
                action: onAccept,
                focus: true
            }]
        });
    },

    error_500(retryCallback) {
        this.show({
            title: 'Error de conexión',
            body: 'No pudimos procesar la solicitud. Por favor intenta nuevamente.',
            buttons: [
                {
                    text: 'Cancelar',
                    variant: 'neutral',
                    action: 'close'
                },
                {
                    text: 'Reintentar',
                    variant: 'primary',
                    action: (instance) => {
                        instance.close();
                        if (typeof retryCallback === 'function') retryCallback();
                    },
                    focus: true
                }
            ]
        });
        loading.end(500);
    },

    procesando_inicio(title = 'Cargando...') {
        this.show({ title, body: '', maskClose: false });
        loading.start();
        document.getElementById('md-body').style.display = 'none';
        document.getElementById('md-loading').style.display = 'block';
    },

    procesando_fin() {
        loading.end(500);
        this.close();
    },

    fast({ title, body, button, cancel = true }) {
        const { text, action } = button;
        const btn_success = { text: text, variant: 'primary', action: (modal) =>  action };
        const btn_cancel = { text: 'Cancelar', variant: 'neutral', action: 'close', focus: true };
        this.show({ title, body, buttons: (cancel ? [btn_success, btn_cancel] : [btn_success] )});
    }
};

/**
 * mytoast - Componente nativo de notificaciones flotantes para PHPost
 * Soporta múltiples posiciones, auto-cierre, cierre manual y acumulación.
 */
const mytoast = {
    _container: null,
    _currentPosition: 'top-right', // Posición por defecto

    // Inicializa o recupera el contenedor según la posición solicitada
    _getContainer(position) {
        // Si cambia la posición solicitada, quitamos el contenedor anterior si está vacío
        if (this._container && this._currentPosition !== position) {
            if (this._container.children.length === 0) {
                this._container.remove();
                this._container = null;
            }
        }

        this._currentPosition = position;
        let container = document.querySelector(`.toast-container.${position}`);

        if (!container) {
            container = document.createElement('div');
            container.className = `toast-container ${position}`;
            document.body.appendChild(container);
        }

        this._container = container;
        return container;
    },

    /**
     * Muestra un toast en pantalla
     * @param {Object} opciones
     */
    show({
        title = '',
        message = '',
        type = 'ok', // 'ok', 'error', 'warning'
        position = 'top-right', // 'top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right'
        duration = 4000, // Tiempo en ms (0 para que no se cierre solo)
        dismissible = true // Si el usuario puede cerrarlo haciendo click
    } = {}) {
        const container = this._getContainer(position);
        // Crear la estructura del toast
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        // Contenido interno del toast
        let htmlNode = '';
        if (title) {
            htmlNode += `<span class="toast-title">${title}</span>`;
        }
        htmlNode += `<div class="toast-message">${message}</div>`;
        toast.innerHTML = htmlNode;
        // Comportamiento de cierre manual
        if (dismissible) {
            toast.style.cursor = 'pointer';
            toast.addEventListener('click', () => this.close(toast));
        }
        // Agregar al contenedor (si es una posición inferior, se puede insertar al inicio o final)
        if (position.startsWith('bottom')) {
            container.insertBefore(toast, container.firstChild); // Los nuevos abajo aparecen primero
        } else {
            container.appendChild(toast);
        }
        // Comportamiento de auto-cierre
        if (duration > 0) {
            setTimeout(() => {
                this.close(toast);
            }, duration);
        }
    },
    // Remueve de forma limpia un toast animando su salida
    close(toast) {
        if (!toast || toast.classList.contains('fade-out')) return;
        toast.classList.add('fade-out');
        // Espera a que termine la animación CSS de salida antes de borrar del DOM
        toast.addEventListener('animationend', () => {
            const container = toast.parentNode;
            toast.remove();
            // Si el contenedor se quedó sin toasts, lo limpiamos del DOM
            if (container && container.children.length === 0) {
                container.remove();
            }
        });
    },
    // Métodos rápidos (Shorthands)
    ok(message, title = '', position = 'top-right', duration = 4000) {
        this.show({ type: 'ok', message, title, position, duration });
    },
    info(message, title = '', position = 'top-right', duration = 4000) {
        this.show({ type: 'info', message, title, position, duration });
    },
    error(message, title = 'Error:', position = 'top-right', duration = 4000) {
        this.show({ type: 'error', message, title, position, duration });
    },
    warning(message, title = 'Atención:', position = 'top-right', duration = 4000) {
        this.show({ type: 'warning', message, title, position, duration });
    }
};


/**
 * PHPost LazyLoad - Native Video & Image Loader (ES6+)
 * Desarrollado con IntersectionObserver para un rendimiento impecable.
 */
const LazyLoadLTS = {
    _observer: null,

    init(selector = '.lazy') {
        // Si el navegador no soporta IntersectionObserver (muy antiguo), cargar todo de inmediato
        if (!('IntersectionObserver' in window)) {
            this.loadAllImmediately(selector);
            return;
        }

        // Configuración del observador
        const options = {
            root: null, // Usa el viewport del navegador
            rootMargin: '200px 0px', // Margen de carga anticipada (200px antes de aparecer en pantalla)
            threshold: 0.01
        };

        this._observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    this.loadElement(target);
                    this._observer.unobserve(target); // Dejar de observar una vez cargado
                }
            });
        }, options);

        // Comenzar a observar todos los elementos que coincidan con el selector
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => this._observer.observe(el));
    },

    loadElement(el) {
        const tagName = el.tagName;

        // Añadir clase de estado inicial
        el.classList.add('loading');

        if (tagName === 'IMG') {
            this._loadSrcs(el);
        } else if (tagName === 'VIDEO') {
            // Si tiene un póster diferido
            if (el.dataset.poster) {
                el.poster = el.dataset.poster;
            }
            // Cargar fuentes internas (<source>)
            const sources = el.querySelectorAll('source');
            if (sources.length > 0) {
                sources.forEach(source => this._loadSrcs(source));
                el.load(); // Forzar al elemento video a recargar los nuevos sources
            } else {
                this._loadSrcs(el);
            }
        } else {
            // Soporte para imágenes de fondo (Background Images) en divs/sections
            if (el.dataset.bg) {
                el.style.backgroundImage = `url("${el.dataset.bg}")`;
                this._markAsLoaded(el);
            }
        }
    },

    _loadSrcs(element) {
        // Manejo de responsive srcset si existe
        if (element.dataset.srcset) {
            element.srcset = element.dataset.srcset;
        }
        // Cargar origen principal
        if (element.dataset.src) {
            element.src = element.dataset.src;
            // Escuchar eventos de carga o fallo para feedback visual
            element.onload = () => this._markAsLoaded(element);
            element.onerror = () => this._markAsError(element);
        }
    },

    _markAsLoaded(element) {
        // Si el elemento es un source, aplicar los estilos al contenedor padre (IMG o VIDEO)
        const target = element.tagName === 'SOURCE' ? element.parentNode : element;
        target.classList.remove('loading');
        target.classList.add('loaded');
    },

    _markAsError(element) {
        const target = element.tagName === 'SOURCE' ? element.parentNode : element;
        target.classList.remove('loading');
        target.classList.add('error');
    },

    loadAllImmediately(selector) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => this.loadElement(el));
    }
};

const formValid = (name, message, status = true) => {
    const helper = document.querySelector(`small[data-target=${name}]`);
    const input = document.querySelector(`input[name="${name}"]`);
    if (!helper || !input) return;
    helper.textContent = message;
    input.classList.toggle('has-ok', status);
    input.classList.toggle('has-error', !status);
    //
    helper.classList.toggle('success', status);
    helper.classList.toggle('danger', !status);
}

// Autoejecución al cargar el DOM
document.addEventListener('DOMContentLoaded', () => LazyLoadLTS.init('.lazy'));