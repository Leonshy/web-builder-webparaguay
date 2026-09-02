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
        input[type=text], input[type=email], input[type=password] { padding:0.5rem 0.6rem; border:1px solid var(--b); border-radius:6px; font:inherit; }
        table { width:100%; border-collapse:collapse; } td,th { text-align:left; padding:0.4rem 0.5rem; border-bottom:1px solid var(--b); }
        header .wrap { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
    </style>
</head>
<body>
<header><div class="wrap">
    <span><strong><a href="{{ route('projects.index') }}" style="text-decoration:none;color:inherit">webparaguay · builder</a></strong></span>
    @auth
        <span class="mut">
            {{ auth()->user()->email }} ·
            <a href="{{ route('profile.edit') }}">Cuenta</a> ·
            <form method="post" action="{{ route('logout') }}" style="display:inline">@csrf<button class="btn" style="background:none;color:var(--acc);padding:0">Salir</button></form>
        </span>
    @endauth
</div></header>
<main class="wrap">{{ $slot }}</main>
</body>
</html>
