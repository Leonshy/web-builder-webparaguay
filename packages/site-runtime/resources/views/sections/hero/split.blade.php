@php
    $c = $content;
    $primary = $ctx->button($c['primary_button'] ?? null);
    $secondary = $ctx->button($c['secondary_button'] ?? null);
@endphp
<section id="{{ $section->domId() }}" @class(['wp-section wp-hero', 'wp-section--dark' => $section->isDark()])>
    <div class="wp-container wp-hero__grid wp-hero__grid--split">
        <div>
            @if($section->label())<span class="wp-eyebrow">{{ $section->label() }}</span>@endif
            @if(!empty($c['headline']))<h1 class="wp-hero__headline" style="margin-top:0.75rem">{{ $c['headline'] }}</h1>@endif
            @if(!empty($c['subheadline']))<p class="wp-hero__sub">{{ $c['subheadline'] }}</p>@endif
            @if(!empty($c['body']))<p class="wp-hero__body">{{ $c['body'] }}</p>@endif
            @if($primary || $secondary)
                <div class="wp-actions">
                    @if($primary)<x-site.button :ctx="$ctx" :button="$primary" />@endif
                    @if($secondary)<x-site.button :ctx="$ctx" :button="$secondary" />@endif
                </div>
            @endif
        </div>

        @if(!empty($c['image']))
            <div class="wp-hero__media-wrap">
                <x-site.image :image="$c['image']" loading="eager" class="wp-media" />
                @if(!empty($c['badge_title']))
                    <div class="wp-hero__badge">
                        <strong>{{ $c['badge_title'] }}</strong>
                        @if(!empty($c['badge_subtitle']))<span>{{ $c['badge_subtitle'] }}</span>@endif
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>
