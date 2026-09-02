@php
    $c = $content;
    $items = $c['items'] ?? [];
    $animate = $c['animate'] ?? true;
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section" center>
    <div class="wp-grid wp-grid--{{ min(count($items), 4) }}" @if($animate) data-wp-count @endif>
        @foreach($items as $item)
            <div class="wp-card wp-card--raised wp-stat" style="text-align:center">
                @if(!empty($item['icon']))<span class="wp-icon-badge" style="margin:0 auto 0.75rem"><x-site.icon :name="$item['icon']" type="stats" /></span>@endif
                <div class="wp-stat__value" data-value="{{ $item['value'] }}" data-suffix="{{ $item['suffix'] ?? '' }}">{{ $item['value'] }}{{ $item['suffix'] ?? '' }}</div>
                <div class="wp-stat__label">{{ $item['label'] }}</div>
            </div>
        @endforeach
    </div>
</x-site.section-shell>
