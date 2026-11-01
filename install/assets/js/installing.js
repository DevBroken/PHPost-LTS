(() => {
    const btn       = document.getElementById('btn-install');
    const finish    = document.getElementById('btn-finish');
    const wrap      = document.getElementById('live-wrap');
    const data      = document.getElementById('data-wrap');
    const log       = document.getElementById('install_live');
    const fill      = document.getElementById('install-bar-fill');

    // Existe el boton?
    if(!btn) return;

    const addLine = text => {
        log.value += text + "\n";
        log.scrollTop = log.scrollHeight;
    }

    const setProgress = (done, total) => {
        let p = total ? Math.round(done/total*100) : 0;
        fill.style.width = p + '%';
    }

    async function call(url) {
        const request = await fetch(url, {
            headers:{
                'X-Requested-With':'XMLHttpRequest'
            }
        });
        const response = await request.json().catch(() => {
            return {
                ok:false,
                msg:'[ERROR] Respuesta inválida'
            };
        });
        return response;
    }

    btn.addEventListener('click', async () => {
        btn.disabled = true; btn.textContent = 'Instalando...';
        wrap.style.display = 'block';
        data.style.display = 'none';
        log.value = '';
        // ACCIONES REALIZADAS
        let jobs = [];
        tables.forEach(n  => jobs.push(['table', n]));
        inserts.forEach(n => jobs.push(['insert', n]));
        // DATOS APLICADOS
        jobs.push(['site', '']);
        jobs.push(['admin', '']);
        //
        let total = jobs.length;
        let okAll = true;

        for(let item = 0; item < jobs.length; item++) {
            let kind = jobs[item][0], nm = jobs[item][1];
            let url = './index.php?step=installing&ajax=' + kind + (nm ? '&name=' + encodeURIComponent(nm) : '');
            addLine('> Ejecutando ' + kind + (nm ? ' ' + nm : '') + '...');
            const res = await call(url);
            addLine(res.msg || (res.ok ? '[OK]' : '[ERROR]'));
            setProgress(item+1, total);
            if(!res.ok) {
                okAll = false;
                break;
            }
        }

        if(okAll) {
            const done = await call('./index.php?step=installing&ajax=done');
            addLine(done.msg);
            setProgress(total, total);
            if(done.ok) {
                btn.style.display = 'none';
                finish.style.display = 'block';
            } else {
                btn.disabled = false;
                btn.textContent = 'Reintentar instalación';
            }
        } else {
            btn.disabled = false;
            btn.textContent = 'Reintentar instalación';
        }
    });
})();