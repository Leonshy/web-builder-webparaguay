@php
    $c = $content;
    $heading = $c['heading'] ?? $section->title() ?? $ctx->page->title();
    $showCrumb = ($c['show_breadcrumb'] ?? true) && !$ctx->page->isHome();
    $home = $ctx->site->homePage();
@endphp
<section id="{{ $section->domId() }}" @class(['wp-section wp-pagehead', 'wp-section--muted' => $section->background() === 'muted', 'wp-section--dark' => $section->isDark()])>
    <div class="wp-container">
        @if($showCrumb && $home)
            <nav class="wp-breadcrumb" aria-label="Ruta de navegación">
                <a href="{{ $ctx->url->pageHref($home) }}">{{ $home->title() }}</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ $heading }}</span>
            </nav>
        @endif
        <span class="wp-icon-badge" style="width:3.5rem;height:3.5rem;margin-bottom:1rem">
            <x-site.icon :name="$c['icon'] ?? null" type="page_header" />
        </span>
        @if($section->label())<span class="wp-eyebrow">{{ $section->label() }}</span>@endif
        <h1 class="wp-title" style="font-size:var(--wp-text-4xl)">{{ $heading }}</h1>
        @if(!empty($c['description']))<p class="wp-pagehead__desc">{{ $c['description'] }}</p>@endif
    </div>
</section>
