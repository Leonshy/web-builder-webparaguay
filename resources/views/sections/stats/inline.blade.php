@php
    $c = $content;
    $items = $c['items'] ?? [];
    $animate = $c['animate'] ?? true;
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section">
    <div class="wp-stats wp-stats--inline" @if($animate) data-wp-count @endif style="gap:2rem">
        @foreach($items as $item)
            <div class="wp-stat" style="text-align:left">
                <span class="wp-stat__value" data-value="{{ $item['value'] }}" data-suffix="{{ $item['suffix'] ?? '' }}">{{ $item['value'] }}{{ $item['suffix'] ?? '' }}</span>
                <span class="wp-stat__label" style="display:block">{{ $item['label'] }}</span>
            </div>
        @endforeach
    </div>
</x-site.section-shell>
