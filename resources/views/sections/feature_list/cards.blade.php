@php
    $c = $content;
    $items = $c['items'] ?? [];
    $cols = $c['columns'] ?? 3;
    $button = $ctx->button($c['button'] ?? null);
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section">
    <div class="wp-grid wp-grid--{{ $cols }}">
        @foreach($items as $item)
            <div class="wp-card wp-card--raised">
                <span class="wp-icon-badge"><x-site.icon :name="$item['icon'] ?? null" type="feature_list" /></span>
                @if(!empty($item['badge']))<span class="wp-badge" style="margin-top:0.75rem">{{ $item['badge'] }}</span>@endif
                <h3 class="wp-item__title" style="margin-top:0.75rem">{{ $item['title'] }}</h3>
                @if(!empty($item['description']))<p class="wp-item__desc">{{ $item['description'] }}</p>@endif
                @if(!empty($item['link']))<div class="wp-actions"><x-site.button :ctx="$ctx" :button="$item['link']" /></div>@endif
            </div>
        @endforeach
    </div>
    @if($button)<div class="wp-actions" style="margin-top:2rem"><x-site.button :ctx="$ctx" :button="$button" /></div>@endif
</x-site.section-shell>
