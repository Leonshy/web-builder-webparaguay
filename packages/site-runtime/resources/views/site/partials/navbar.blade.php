@php
    $nav = $ctx->navigation;
    $navbar = $ctx->site->layout()['navbar'] ?? [];
    $logo = $navbar['logo'] ?? null;
    $navButton = $ctx->button($navbar['button'] ?? null);
    $home = $ctx->site->homePage();
    $homeHref = $home ? $ctx->url->pageHref($home) : '/';
    $sticky = $navbar['sticky'] ?? true;
    $anchors = collect($nav)->firstWhere('current')['children'] ?? [];
@endphp
<header @class(['wp-navbar', 'wp-navbar--sticky' => $sticky])>
    <div class="wp-container wp-navbar__bar">
        <a href="{{ $homeHref }}" class="wp-navbar__brand">
            @if($logo && !empty($logo['src']))
                <img src="{{ $logo['src'] }}" alt="{{ $logo['alt'] ?? $ctx->site->businessName() }}">
            @else
                {{ $ctx->site->businessName() }}
            @endif
        </a>

        <details class="wp-navbar__disclosure">
            <summary class="wp-navbar__burger" aria-label="Abrir y cerrar el menú">
                <span class="wp-navbar__burger-bar" aria-hidden="true"></span>
                <span class="wp-navbar__burger-bar" aria-hidden="true"></span>
                <span class="wp-navbar__burger-bar" aria-hidden="true"></span>
            </summary>

            <div class="wp-navbar__panel">
                @if(count($nav))
                    <nav class="wp-navbar__links" aria-label="Principal">
                        @foreach($nav as $item)
                            <a href="{{ $item['href'] }}" class="wp-navlink" @if($item['current']) aria-current="page" @endif>{{ $item['label'] }}</a>
                        @endforeach
                    </nav>
                @endif

                @if(count($anchors) > 1)
                    <nav class="wp-navbar__anchors" aria-label="Secciones de esta página">
                        @foreach($anchors as $anchor)
                            <a href="{{ $anchor['href'] }}" class="wp-navlink">{{ $anchor['label'] }}</a>
                        @endforeach
                    </nav>
                @endif

                @if($navButton)
                    <x-site.button :ctx="$ctx" :button="$navButton" />
                @endif
            </div>
        </details>
    </div>

    @if(count($anchors) > 1)
        <div class="wp-navbar__substrip">
            <div class="wp-container" style="display:flex;gap:1.25rem;flex-wrap:wrap;padding-block:0.55rem;overflow-x:auto">
                @foreach($anchors as $anchor)
                    <a href="{{ $anchor['href'] }}" class="wp-navlink" style="white-space:nowrap">{{ $anchor['label'] }}</a>
                @endforeach
            </div>
        </div>
    @endif
</header>
