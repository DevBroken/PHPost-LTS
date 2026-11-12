// Mantenemos la estructura de objeto literal solicitada
const live = {
    update_time: 30000,
    hide_time: 20000,
    status: { nots: 'ON', mps: 'ON', sound: 'ON' },
    focus: true,
    notifica_total: 0,
    mensaje_total: 0,
    inicializar: function() {
        ['nots', 'mps', 'sound'].forEach(type => {
            this.status[type] = CookieManager.get(`live_${type}`);
            if (this.status[type] === null) {
                CookieManager.set(`live_${type}`, 'ON', { expires: 90 });
                this.status[type] = 'ON';
            }
        });
        if (this.status['nots'] === 'OFF' && this.status['mps'] === 'OFF') {
            return true;
        }
        setTimeout(() => this.update(), this.update_time);
    },

    print: function(response) {
        let $js = $('#js');
        if (!$js.length) {
            $js = $('<div id="js"></div>').appendTo('body');
        }
        $js.html(response);

        const $liveStream = $('#live-stream');
        if (!$liveStream.length) return;

        // Obtenemos totales de los atributos data de forma segura
        const notifica_total = parseInt($liveStream.attr('ntotal') || 0, 10);
        const mensaje_total = parseInt($liveStream.attr('mtotal') || 0, 10);

        this.notifica_total += notifica_total;
        this.mensaje_total += mensaje_total;
        const total_notis = this.notifica_total + this.mensaje_total;

        if (total_notis > 0) {
            const live_stream_html = $liveStream.html();
            let $beeperBox = $('#BeeperBox');
            if (!$beeperBox.length) {
                $beeperBox = $('<div id="BeeperBox"></div>').appendTo('body');
            }
            $beeperBox.html(live_stream_html);
            $('.UIBeeper_Full').fadeIn(1200);
            $('#BeeperBox').slideToggle(1000);
            // Eventos del mouse
            this.mouse_events();
            // Si estamos en la página activa
            if (this.focus === true) {
                setTimeout(() => { this.hide(); }, this.hide_time);
            } else {
                document.title = `${site.title} (${total_notis}) - ${site.slogan}`;
                const sound_type = (this.mensaje_total > 0) ? 'newMessage' : 'newAlert';
                if (this.status['sound'] === 'ON') {
                    let $swf = $('#swf');
                    if (!$swf.length) {
                        $swf = $('<div id="swf"></div>').appendTo('body');
                    }
                    $swf.html(`<embed width="1px" height="1px" wmode="transparent" allowscriptaccess="always" quality="high" bgcolor="#ffffff" src="${global_data.url}/assets/sounds/${sound_type}.swf" type="application/x-shockwave-flash">`);
                }
                if (typeof notifica !== 'undefined') notifica.popup(this.notifica_total);
                if (typeof mensaje !== 'undefined') mensaje.popup(this.mensaje_total);
            }
        }
    },
    mouse_events: function() {
        $('.UIBeep').on('mouseenter', function() {
            const $this = $(this);
            $this.addClass('UIBeep_Selected');
            $this.parent().parent().addClass('UIBeep_Paused');
        }).on('mouseleave', function() {
            const $this = $(this);
            $this.removeClass('UIBeep_Selected');
            $this.parent().parent().removeClass('UIBeep_Paused');
            live.hide();
        });
    },
    update: async function() {
        if (typeof loading !== 'undefined') loading.start();
        try {
            const response = await $.post('/live-stream', {
                nots: this.status['nots'],
                mps: this.status['mps']
            });
            this.print(response);
        } catch (error) {
            console.error('Error en live stream update:', error);
        } finally {
            setTimeout(() => this.update(), this.update_time);
            if (typeof loading !== 'undefined') loading.end(500);
        }
    },
    hide: function() {
        const divs = $('.UIBeeper_Full');
        if (divs.length > 0) {
            setTimeout(() => {
                const $firstDiv = $(divs[0]);
                if (!$firstDiv.hasClass('UIBeep_Paused')) {
                    $firstDiv.fadeOut(() => {
                        $firstDiv.remove();
                        this.hide();
                    });
                }
            }, 1000);
        }
    },
    ch_status: function(type) {
        this.status[type] = (this.status[type] === 'ON') ? 'OFF' : 'ON';
        CookieManager.set(`live_${type}`, this.status[type], { expires: 90 });
    }
};

const CookieManager = {
    set(name, value, options = {}) {
        if (value === null || value === '') {
            value = '';
            options.expires = -1;
        }
        let expires = '';
        if (options.expires) {
            const date = new Date();
            date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            expires = `; expires=${date.toUTCString()}`;
        }
        const path = options.path ? `; path=${options.path}` : '';
        const domain = options.domain ? `; domain=${options.domain}` : '';
        const secure = options.secure ? '; secure' : '';
        document.cookie = `${name}=${encodeURIComponent(value)}${expires}${path}${domain}${secure}`;
    },
    get(name) {
        if (document.cookie && document.cookie !== '') {
            const cookies = document.cookie.split(';');
            for (let cookie of cookies) {
                cookie = cookie.trim();
                if (cookie.startsWith(`${name}=`)) {
                    return decodeURIComponent(cookie.substring(name.length + 1));
                }
            }
        }
        return null;
    }
};

$(() => {
    $(document).on('click', '.beeper_x', function(e) {
        e.preventDefault();
        const bid = $(this).attr('bid');
        $(`#beep_${bid}`).fadeOut(() => {
            $(this).remove();
        });
    });
    live.inicializar();
    $(window).on('focus', () => live.focus = true).on('blur', () => live.focus = false);
});
