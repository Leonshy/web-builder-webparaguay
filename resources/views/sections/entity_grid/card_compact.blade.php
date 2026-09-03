@php
    $c = $content;
    $source = $c['source'] ?? 'manual';
    $items = array_slice($c['items'] ?? [], 0, $c['limit'] ?? 24);
    $cols = $c['columns'] ?? 3;
    $button = $ctx->button($c['button'] ?? null);
@endphp
@if($source !== 'manual')
    @if(config('app.debug'))<section class="wp-section wp-container"><p><code>entity_grid source={{ $source }}</code> llega en la v1.</p></section>@endif
@else
<x-site.section-shell :ctx="$ctx" :section="$section">
    <div class="wp-grid wp-grid--{{ $cols }}">
        @foreach($items as $item)
            <article class="wp-card wp-entity wp-entity--compact">
                @if(!empty($item['image']))<x-site.image :image="$item['image']" class="wp-media wp-entity__media" />@endif
                <h3 class="wp-item__title">{{ $item['title'] }}</h3>
                @if(!empty($item['link']))<x-site.button :ctx="$ctx" :button="$item['link']" />@endif
            </article>
        @endforeach
    </div>
    @if($button)<div class="wp-actions" style="margin-top:2rem"><x-site.button :ctx="$ctx" :button="$button" /></div>@endif
</x-site.section-shell>
@endif
