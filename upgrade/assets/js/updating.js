(() => {
    const btn = document.getElementById('btn-upgrade');
    const finish = document.getElementById('btn-finish');
    const log = document.getElementById('upgrade_live');
    const fill = document.getElementById('install-bar-fill');
    if (!btn) return;

    const add = (t) => { log.value += t + "\n"; log.scrollTop = log.scrollHeight; };
    const prog = (d, t) => { fill.style.width = (t ? Math.round(d / t * 100) : 0) + '%'; };
    const call = async (url) => {
        const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        return r.json().catch(() => ({ ok: false, msg: '[ERROR] Respuesta inválida' }));
    };

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        btn.textContent = 'Actualizando...';
        log.value = '';
        const jobs = [];
        (typeof upTables !== 'undefined' ? upTables : []).forEach((n) => jobs.push(['table', n]));
        jobs.push(['columns', '']);
        jobs.push(['indexes', '']);
        jobs.push(['passwords', '']);
        jobs.push(['ips', '']);
        jobs.push(['statuses', '']);
        jobs.push(['seeds', '']);
        const total = jobs.length;
        let ok = true;
        for (let i = 0; i < jobs.length; i++) {
            const [kind, nm] = jobs[i];
            add('> Ejecutando ' + kind + (nm ? ' ' + nm : '') + '...');
            const force = (kind === 'statuses' && typeof upForceStatuses !== 'undefined' && upForceStatuses) ? '&force=1' : '';
            const res = await call('./index.php?step=updating&ajax=' + kind + (nm ? '&name=' + encodeURIComponent(nm) : '') + force);
            add(res.msg || (res.ok ? '[OK]' : '[ERROR]'));
            prog(i + 1, total);
            if (!res.ok) { ok = false; break; }
        }
        if (ok) {
            const d = await call('./index.php?step=updating&ajax=done');
            add(d.msg);
            if (d.ok) { btn.style.display = 'none'; finish.style.display = 'block'; }
            else { btn.disabled = false; btn.textContent = 'Reintentar'; }
        } else {
            btn.disabled = false;
            btn.textContent = 'Reintentar';
        }
    });
})();
