@php
    $c = $content;
    $items = $c['items'] ?? [];
    $button = $ctx->button($c['button'] ?? null);
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section" inner="wp-container wp-container-narrow">
    <div class="wp-timeline">
        @foreach($items as $i => $item)
            {{-- La variante timeline fuerza numbered=true --}}
            <div class="wp-timeline__row">
                <span class="wp-num">{{ $i + 1 }}</span>
                <div>
                    <h3 class="wp-item__title">{{ $item['title'] }}</h3>
                    @if(!empty($item['description']))<p class="wp-item__desc">{{ $item['description'] }}</p>@endif
                </div>
            </div>
        @endforeach
    </div>
    @if($button)<div class="wp-actions" style="margin-top:2rem"><x-site.button :ctx="$ctx" :button="$button" /></div>@endif
</x-site.section-shell>
