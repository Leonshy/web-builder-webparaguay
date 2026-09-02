@php
    $nav = $ctx->navigation;
    $navbar = $ctx->site->layout()['navbar'] ?? [];
    $logo = $navbar['logo'] ?? null;
    $navButton = $ctx->button($navbar['button'] ?? null);
    $home = $ctx->site->homePage();
    $homeHref = $home ? $ctx->url->pageHref($home) : '/';
    $sticky = $navbar['sticky'] ?? true;
@endphp
<header @class(['wp-navbar', 'wp-navbar--sticky' => $sticky])>
    <nav class="wp-container" style="display:flex;align-items:center;justify-content:space-between;gap:1.5rem;padding-block:0.9rem" aria-label="Principal">
        <a href="{{ $homeHref }}" style="display:inline-flex;align-items:center;gap:0.6rem;font-family:var(--wp-font-heading);font-weight:var(--wp-weight-heading);font-size:var(--wp-text-lg)">
            @if($logo && !empty($logo['src']))
                <img src="{{ $logo['src'] }}" alt="{{ $logo['alt'] ?? $ctx->site->businessName() }}" style="height:2rem;width:auto">
            @else
                {{ $ctx->site->businessName() }}
            @endif
        </a>

        @if(count($nav))
            <ul style="display:flex;gap:1.5rem;align-items:center;list-style:none;flex-wrap:wrap" class="wp-nav-list">
                @foreach($nav as $item)
                    <li>
                        <a href="{{ $item['href'] }}" class="wp-navlink" @if($item['current']) aria-current="page" @endif>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        @endif

        @if($navButton)
            <x-site.button :ctx="$ctx" :button="$navButton" />
        @endif
    </nav>

    @php($anchors = collect($nav)->firstWhere('current')['children'] ?? [])
    @if(count($anchors) > 1)
        <div style="border-top:1px solid var(--wp-border)">
            <div class="wp-container" style="display:flex;gap:1.25rem;flex-wrap:wrap;padding-block:0.55rem;overflow-x:auto">
                @foreach($anchors as $anchor)
                    <a href="{{ $anchor['href'] }}" class="wp-navlink" style="white-space:nowrap">{{ $anchor['label'] }}</a>
                @endforeach
            </div>
        </div>
    @endif
</header>
