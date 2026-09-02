@php
    $c = $content;
    $items = $c['items'] ?? [];
    $button = $ctx->button($c['button'] ?? null);
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section">
    <div class="wp-stack-lg">
        @foreach($items as $item)
            <div class="wp-bar">
                <span class="wp-icon-badge"><x-site.icon :name="$item['icon'] ?? null" type="feature_list" /></span>
                <div>
                    <h3 class="wp-item__title">{{ $item['title'] }}</h3>
                    @if(!empty($item['description']))<p class="wp-item__desc">{{ $item['description'] }}</p>@endif
                </div>
            </div>
        @endforeach
    </div>
    @if($button)<div class="wp-actions" style="margin-top:1.5rem"><x-site.button :ctx="$ctx" :button="$button" /></div>@endif
</x-site.section-shell>
