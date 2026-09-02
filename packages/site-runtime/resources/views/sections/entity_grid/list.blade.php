@php
    $c = $content;
    $source = $c['source'] ?? 'manual';
    $items = array_slice($c['items'] ?? [], 0, $c['limit'] ?? 24);
    $button = $ctx->button($c['button'] ?? null);
@endphp
@if($source !== 'manual')
    @if(config('app.debug'))<section class="wp-section wp-container"><p><code>entity_grid source={{ $source }}</code> llega en la v1.</p></section>@endif
@else
<x-site.section-shell :ctx="$ctx" :section="$section" inner="wp-container wp-container-narrow">
    <ul class="wp-entity-list">
        @foreach($items as $item)
            <li class="wp-entity-list__row">
                @if(!empty($item['icon']))<span class="wp-icon-badge"><x-site.icon :name="$item['icon']" type="entity_grid" /></span>@endif
                <div>
                    @if(!empty($item['badge']))<span class="wp-badge">{{ $item['badge'] }}</span>@endif
                    <h3 class="wp-item__title">{{ $item['title'] }}</h3>
                    @if(!empty($item['description']))<p class="wp-item__desc">{{ $item['description'] }}</p>@endif
                    @if(!empty($item['link']))<div class="wp-actions"><x-site.button :ctx="$ctx" :button="$item['link']" /></div>@endif
                </div>
            </li>
        @endforeach
    </ul>
    @if($button)<div class="wp-actions" style="margin-top:2rem"><x-site.button :ctx="$ctx" :button="$button" /></div>@endif
</x-site.section-shell>
@endif
