@php
    $c = $content;
    $heading = $c['heading'] ?? $section->title() ?? $ctx->page->title();
    $showCrumb = ($c['show_breadcrumb'] ?? true) && !$ctx->page->isHome();
    $home = $ctx->site->homePage();
    $bg = $section->backgroundImage();
@endphp
<section id="{{ $section->domId() }}" class="wp-section wp-pagehead wp-pagehead--image wp-section--image"
    @if($bg && !empty($bg['src'])) style="background-image:url('{{ $bg['src'] }}')" @endif>
    <div class="wp-container">
        @if($showCrumb && $home)
            <nav class="wp-breadcrumb" aria-label="Ruta de navegación">
                <a href="{{ $ctx->url->pageHref($home) }}">{{ $home->title() }}</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ $heading }}</span>
            </nav>
        @endif
        @if($section->label())<span class="wp-eyebrow">{{ $section->label() }}</span>@endif
        <h1 class="wp-title" style="font-size:var(--wp-text-4xl)">{{ $heading }}</h1>
        @if(!empty($c['description']))<p class="wp-pagehead__desc">{{ $c['description'] }}</p>@endif
    </div>
</section>
