<!DOCTYPE html>
<html lang="es-PY">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title ?? 'Proyectos' }} — webparaguay builder</title>
    <style>
        :root { color-scheme: light dark; --b:#d9d9de; --mut:#6b6b73; --acc:#1f6f5c; }
        body { margin:0; font:15px/1.55 ui-sans-serif,system-ui,sans-serif; }
        .wrap { max-width:52rem; margin:0 auto; padding:1.5rem; }
        header { border-bottom:1px solid var(--b); }
        header .wrap { padding-block:0.9rem; }
        a { color:var(--acc); }
        .card { border:1px solid var(--b); border-radius:10px; padding:1rem 1.25rem; margin-bottom:0.75rem; }
        .mut { color:var(--mut); font-size:0.85rem; }
        .btn { display:inline-block; padding:0.5rem 1rem; border-radius:7px; background:var(--acc); color:#fff; text-decoration:none; border:0; font:inherit; cursor:pointer; }
        input[type=text] { padding:0.5rem 0.6rem; border:1px solid var(--b); border-radius:6px; font:inherit; }
        table { width:100%; border-collapse:collapse; } td,th { text-align:left; padding:0.4rem 0.5rem; border-bottom:1px solid var(--b); }
    </style>
</head>
<body>
<header><div class="wrap"><strong>webparaguay · builder</strong> <span class="mut">— cuentas, proyectos, consumo de IA</span></div></header>
<main class="wrap">{{ $slot }}</main>
</body>
</html>
