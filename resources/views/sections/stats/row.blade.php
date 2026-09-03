@php
    $c = $content;
    $items = $c['items'] ?? [];
    $animate = $c['animate'] ?? true;
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section" center>
    <div class="wp-stats wp-stats--row" @if($animate) data-wp-count @endif>
        @foreach($items as $item)
            <div class="wp-stat">
                @if(!empty($item['icon']))<x-site.icon :name="$item['icon']" type="stats" />@endif
                <div class="wp-stat__value" data-value="{{ $item['value'] }}" data-suffix="{{ $item['suffix'] ?? '' }}">{{ $item['value'] }}{{ $item['suffix'] ?? '' }}</div>
                <div class="wp-stat__label">{{ $item['label'] }}</div>
            </div>
        @endforeach
    </div>
</x-site.section-shell>
