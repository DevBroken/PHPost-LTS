<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PHPost › Error del Sistema</title>
<style>
    :root {
        /* Obligatorio para que funcione light-dark() */
        color-scheme: light dark;

        /* Paleta de colores híbrida dinámica */
        --bg-main: light-dark(#f8fafc, #0f172a);
        --panel-bg: light-dark(#ffffff, #1e293b);
        --text-dark: light-dark(#1e293b, #f8fafc);
        --text-muted: light-dark(#64748b, #94a3b8);
        --border-color: light-dark(#e2e8f0, #334155);

        /* Bloque de error (Rojo) */
        --accent-red: light-dark(#e11d48, #f43f5e);
        --accent-red-bg: light-dark(#fff1f2, #4c0519);
        --accent-red-text: light-dark(#991b1b, #ffe4e6);

        /* Bloques de código (SQL) */
        --code-bg: light-dark(#0f172a, #020617);
        --code-text: light-dark(#f8fafc, #38bdf8);

        /* Cabecera del panel */
        --header-bg: light-dark(#fafafa, #1e293b);
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html, body {
        width: 100%;
        height: 100%;
        background-color: var(--bg-main);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: var(--text-dark);
        display: grid;
        place-items: center center;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    main {
        width: 100%;
        max-width: 780px;
        margin: 1.5rem;
        background: var(--panel-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
        overflow: hidden;
    }

    header {
        background: var(--header-bg);
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    header .badge {
        background: var(--accent-red);
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    header h1 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-dark);
    }

    .content-body {
        padding: 1.5rem;
    }

    .error-message-box {
        background-color: var(--accent-red-bg);
        border-left: 4px solid var(--accent-red);
        padding: 1rem 1.25rem;
        border-radius: 0 8px 8px 0;
        font-size: 0.95rem;
        font-weight: 500;
        line-height: 1.5;
        color: var(--accent-red-text);
        margin-bottom: 1.5rem;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
        margin-top: 1rem;
        box-sizing: border-box;
    }

    table th {
        width: 25%;
        text-align: left;
        padding: 0.75rem 1rem;
        color: var(--text-muted);
        font-weight: 600;
        border-bottom: 1px solid var(--border-color);
        vertical-align: top;
    }

    table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-dark);
        word-break: break-all;
        span {
            color: var(--text-muted);
            font-weight: 600;
        }
    }

    table tr:last-child th,
    table tr:last-child td {
        border-bottom: none;
    }

    pre {
        background-color: var(--code-bg);
        color: var(--code-text);
        padding: 1rem;
        border-radius: 8px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        text-wrap: wrap stable;
        font-size: 0.985rem;
        line-height: 1.6;
        margin-top: 0.5rem;
        width: 700px;
        overflow-x: auto;
        code {
            width: 100%;
            max-width: 700px;
            box-sizing: content-box;
        }
    }

    .mysql-raw {
        font-family: monospace;
        color: light-dark(#c2410c, #ff7a00);
        background: light-dark(#fff7ed, #2c1a04);
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
        font-size: 0.85rem;
        border: 1px solid light-dark(#ffedd5, #4a2b00);
        display: inline-block;
    }
</style>

</head>
<body>
    <main>
        <header>
            <span class="badge">Sistema: <?= $type ?></span>
            <h1>PHPost LTS</h1>
        </header>
        <div class="content-body">
            <div class="error-message-box">
                <?= $type === 'plantilla' ? $error : htmlspecialchars($error) ?>
            </div>
            <?= $table ?>
        </div>
    </main>
</body>
</html>
<?php exit(); ?>
