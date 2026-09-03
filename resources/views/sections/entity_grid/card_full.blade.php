@php
    $c = $content;
    $source = $c['source'] ?? 'manual';
    $items = array_slice($c['items'] ?? [], 0, $c['limit'] ?? 24);
    $cols = $c['columns'] ?? 3;
    $button = $ctx->button($c['button'] ?? null);
@endphp
@if($source !== 'manual')
    {{-- Punto de extensión v1: colecciones conectadas. En el MVP sólo source=manual. --}}
    @if(config('app.debug'))<section class="wp-section wp-container"><p><code>entity_grid source={{ $source }}</code> llega en la v1.</p></section>@endif
@else
<x-site.section-shell :ctx="$ctx" :section="$section">
    <div class="wp-grid wp-grid--{{ $cols }}">
        @foreach($items as $item)
            <article class="wp-card wp-entity">
                @if(!empty($item['image']))<x-site.image :image="$item['image']" class="wp-media wp-entity__media" />@endif
                <div class="wp-entity__body">
                    @if(!empty($item['badge']))<span class="wp-badge">{{ $item['badge'] }}</span>@endif
                    <h3 class="wp-item__title">{{ $item['title'] }}</h3>
                    @if(!empty($item['description']))<p class="wp-item__desc">{{ $item['description'] }}</p>@endif
                    @if(!empty($item['link']))<div class="wp-actions"><x-site.button :ctx="$ctx" :button="$item['link']" /></div>@endif
                </div>
            </article>
        @endforeach
    </div>
    @if($button)<div class="wp-actions" style="margin-top:2rem"><x-site.button :ctx="$ctx" :button="$button" /></div>@endif
</x-site.section-shell>
@endif
