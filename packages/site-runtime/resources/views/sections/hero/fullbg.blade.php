@php
    $c = $content;
    $primary = $ctx->button($c['primary_button'] ?? null);
    $secondary = $ctx->button($c['secondary_button'] ?? null);
    $bg = $section->backgroundImage() ?? ($c['image'] ?? null);
@endphp
<section id="{{ $section->domId() }}" class="wp-section wp-hero wp-hero--onimage wp-section--image"
    @if($bg && !empty($bg['src'])) style="background-image:url('{{ $bg['src'] }}')" @endif>
    <div class="wp-container wp-hero--center">
        @if($section->label())<span class="wp-eyebrow">{{ $section->label() }}</span>@endif
        @if(!empty($c['headline']))<h1 class="wp-hero__headline" style="margin-top:0.75rem">{{ $c['headline'] }}</h1>@endif
        @if(!empty($c['subheadline']))<p class="wp-hero__sub">{{ $c['subheadline'] }}</p>@endif
        @if(!empty($c['body']))<p class="wp-hero__body" style="margin-inline:auto">{{ $c['body'] }}</p>@endif
        @if($primary || $secondary)
            <div class="wp-actions">
                @if($primary)<x-site.button :ctx="$ctx" :button="$primary" />@endif
                @if($secondary)<x-site.button :ctx="$ctx" :button="$secondary" />@endif
            </div>
        @endif
    </div>
</section>
