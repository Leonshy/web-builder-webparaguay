@php($settings = $ctx->settings())
<!DOCTYPE html>
<html lang="es-PY">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $ctx->pageTitle() }}</title>
    @if($ctx->metaDescription())
        <meta name="description" content="{{ $ctx->metaDescription() }}">
    @endif
    @if($ctx->noindex())
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ $ctx->theme->googleFontsUrl() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Tokens de marca DESPUÉS del CSS base: sobreescriben los fallbacks neutros de :root. --}}
    <style>{!! $ctx->theme->rootBlock() !!}</style>
</head>
<body>
    <a href="#contenido" class="wp-skip-link">Saltar al contenido</a>

    @include('site.partials.navbar')

    <main id="contenido">
        @yield('content')
    </main>

    @include('site.partials.footer')
</body>
</html>
