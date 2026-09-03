@php
    $c = $content;
    $items = $c['items'] ?? [];
    $autoplay = $c['autoplay'] ?? true;
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section" center force-dark>
    <div class="wp-slider" @if($autoplay) data-wp-slider @endif>
        @foreach($items as $item)
            <div class="wp-slider__slide">
                @include('sections.testimonials._card', ['item' => $item])
            </div>
        @endforeach
    </div>
</x-site.section-shell>
