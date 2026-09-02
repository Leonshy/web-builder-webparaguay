@php
    $c = $content;
    $primary = $ctx->button($c['primary_button'] ?? null);
@endphp
<section id="{{ $section->domId() }}" @class(['wp-section wp-hero', 'wp-section--dark' => $section->isDark()])>
    <div class="wp-container wp-hero--center">
        @if($section->label())<span class="wp-eyebrow">{{ $section->label() }}</span>@endif
        @if(!empty($c['headline']))<h1 class="wp-hero__headline" style="margin-top:0.75rem">{{ $c['headline'] }}</h1>@endif
        @if(!empty($c['subheadline']))<p class="wp-hero__sub">{{ $c['subheadline'] }}</p>@endif
        @if($primary)
            <div class="wp-actions"><x-site.button :ctx="$ctx" :button="$primary" /></div>
        @endif
    </div>
</section>
