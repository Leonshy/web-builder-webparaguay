@php
    $c = $content;
    $items = $c['items'] ?? [];
    $cols = $c['columns'] ?? 3;
    $numbered = $c['numbered'] ?? false;
    $button = $ctx->button($c['button'] ?? null);
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section">
    <div class="wp-grid wp-grid--{{ $cols }}">
        @foreach($items as $i => $item)
            <x-site.item :ctx="$ctx" :item="$item" iconType="feature_list"
                :number="$numbered ? $i + 1 : null" />
        @endforeach
    </div>
    @if($button)<div class="wp-actions" style="margin-top:2rem"><x-site.button :ctx="$ctx" :button="$button" /></div>@endif
</x-site.section-shell>
