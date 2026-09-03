@php
    $c = $content;
    $slides = $c['slides'] ?? [];
@endphp
<section id="{{ $section->domId() }}" @class(['wp-section wp-hero', 'wp-section--dark' => $section->isDark()])
    data-wp-carousel aria-roledescription="carrusel">
    @if($section->label())
        <div class="wp-container"><span class="wp-eyebrow">{{ $section->label() }}</span></div>
    @endif
    <div class="wp-hero__slides">
        @foreach($slides as $i => $slide)
            @php($primary = $ctx->button($slide['primary_button'] ?? null))
            <div @class(['wp-hero__slide', 'is-active' => $i === 0]) role="group" aria-roledescription="diapositiva"
                 aria-label="{{ $i + 1 }} de {{ count($slides) }}">
                <div class="wp-container wp-hero__grid wp-hero__grid--split">
                    <div>
                        <h1 class="wp-hero__headline">{{ $slide['headline'] }}</h1>
                        @if(!empty($slide['subheadline']))<p class="wp-hero__sub">{{ $slide['subheadline'] }}</p>@endif
                        @if(!empty($slide['body']))<p class="wp-hero__body">{{ $slide['body'] }}</p>@endif
                        @if($primary)<div class="wp-actions"><x-site.button :ctx="$ctx" :button="$primary" /></div>@endif
                    </div>
                    @if(!empty($slide['image']))
                        <div class="wp-hero__media-wrap">
                            <x-site.image :image="$slide['image']" :loading="$i === 0 ? 'eager' : 'lazy'" class="wp-media" />
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
