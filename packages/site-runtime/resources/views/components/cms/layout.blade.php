<!DOCTYPE html>
<html lang="es-PY">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'CMS' }} — webparaguay</title>
    <style>
        :root { color-scheme: light dark; --b: #d9d9de; --mut: #6b6b73; --acc: #1f6f5c; --bg: #fbfbfa; --card: #fff; }
        @media (prefers-color-scheme: dark) { :root { --b:#3a3a40; --mut:#a2a2ab; --bg:#161618; --card:#1f1f22; } }
        * { box-sizing: border-box; }
        body { margin: 0; font: 15px/1.55 ui-sans-serif, system-ui, sans-serif; background: var(--bg); color: inherit; }
        a { color: var(--acc); }
        .cms-wrap { max-width: 60rem; margin: 0 auto; padding: 1.5rem; }
        header.cms-top { border-bottom: 1px solid var(--b); }
        header.cms-top .cms-wrap { display: flex; gap: 1rem; align-items: center; padding-block: 0.9rem; }
        header.cms-top strong { font-size: 1.05rem; }
        .cms-crumbs { color: var(--mut); font-size: 0.85rem; margin-bottom: 1.25rem; }
        .cms-card { background: var(--card); border: 1px solid var(--b); border-radius: 10px; padding: 1.25rem; margin-bottom: 1rem; }
        h1 { font-size: 1.4rem; margin: 0 0 1rem; }
        h2 { font-size: 1.1rem; margin: 0 0 0.75rem; }
        .cms-ok { background: color-mix(in srgb, var(--acc) 14%, transparent); border: 1px solid var(--acc); border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem; }
        .cms-err { background: color-mix(in srgb, #d33 12%, transparent); border: 1px solid #d33; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem; }
        .cms-err ul { margin: 0.4rem 0 0; padding-left: 1.2rem; }
        .cms-field { margin-bottom: 0.9rem; }
        .cms-field__label { display: flex; gap: 0.5rem; align-items: baseline; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.3rem; }
        .cms-hint { font-weight: 400; color: var(--mut); font-size: 0.75rem; }
        .cms-req { color: #d33; }
        input[type=text], input[type=number], input[type=email], input[type=url], textarea, select {
            width: 100%; padding: 0.5rem 0.6rem; border: 1px solid var(--b); border-radius: 6px; background: var(--bg); color: inherit; font: inherit;
        }
        textarea { resize: vertical; }
        .cms-check { display: flex; gap: 0.45rem; align-items: center; font-weight: 400; }
        .cms-checks { display: flex; flex-wrap: wrap; gap: 0.75rem; }
        .cms-color { display: flex; gap: 0.5rem; align-items: center; }
        .cms-color input[type=color] { width: 3rem; height: 2.2rem; padding: 0; border: 1px solid var(--b); border-radius: 6px; }
        .cms-nest, fieldset.cms-nest { border: 1px dashed var(--b); border-radius: 8px; padding: 0.85rem; display: grid; gap: 0.6rem; }
        fieldset { border: 1px solid var(--b); border-radius: 8px; padding: 0.85rem; margin: 0 0 0.6rem; }
        .cms-list__row { position: relative; }
        .cms-list__del { position: absolute; top: 0.5rem; right: 0.6rem; font-size: 0.75rem; color: var(--mut); }
        .cms-btn { display: inline-flex; gap: 0.4rem; align-items: center; padding: 0.55rem 1rem; border-radius: 7px; border: 1px solid var(--acc); background: var(--acc); color: #fff; font: inherit; font-weight: 600; cursor: pointer; text-decoration: none; }
        .cms-btn--ghost { background: transparent; color: var(--acc); }
        .cms-btn--danger { border-color: #d33; background: transparent; color: #d33; }
        .cms-row { display: flex; gap: 0.6rem; flex-wrap: wrap; align-items: center; }
        .cms-list-item { display: flex; gap: 0.75rem; align-items: center; justify-content: space-between; border: 1px solid var(--b); border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 0.5rem; }
        .cms-muted { color: var(--mut); font-size: 0.85rem; }
        table.cms-t { width: 100%; border-collapse: collapse; }
        table.cms-t td, table.cms-t th { text-align: left; padding: 0.5rem; border-bottom: 1px solid var(--b); }
    </style>
</head>
<body>
    <header class="cms-top">
        <div class="cms-wrap">
            <strong><a href="{{ route('cms.index') }}" style="text-decoration:none;color:inherit">webparaguay · CMS</a></strong>
            <span class="cms-muted">datos = JSON validado contra el esquema</span>
        </div>
    </header>
    <main class="cms-wrap">
        @if(session('ok'))<div class="cms-ok">{{ session('ok') }}</div>@endif
        @if($errors->any())
            <div class="cms-err">
                <strong>No se guardó.</strong> El sitio no valida contra el esquema:
                <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        {{ $slot }}
    </main>

    <script>
        // Listas repetibles: clonar la plantilla, renumerar el índice.
        document.querySelectorAll('[data-list-add]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const list = btn.previousElementSibling;
                const tpl = list.querySelector('[data-list-template]');
                const idx = list.querySelectorAll('.cms-list__row').length;
                const html = tpl.innerHTML.replaceAll('__IDX__', idx);
                tpl.insertAdjacentHTML('beforebegin', html);
            });
        });
        // Cambiar de variante envía el form al instante (el contenido se conserva en el server).
        document.querySelectorAll('[data-variant-select]').forEach((sel) => {
            sel.addEventListener('change', () => sel.closest('form').submit());
        });
    </script>
</body>
</html>
