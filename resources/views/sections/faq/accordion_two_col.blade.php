@php
    $c = $content;
    $items = $c['items'] ?? [];
    $half = (int) ceil(count($items) / 2);
    $cols = [array_slice($items, 0, $half), array_slice($items, $half)];
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section">
    <div class="wp-faq wp-faq--two-col">
        @foreach($cols as $col)
            <div class="wp-faq__col">
                @foreach($col as $item)
                    @include('sections.faq._item', ['item' => $item])
                @endforeach
            </div>
        @endforeach
    </div>
</x-site.section-shell>
