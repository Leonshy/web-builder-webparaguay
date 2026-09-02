@php($c = $content)
<x-site.section-shell :ctx="$ctx" :section="$section" inner="wp-container wp-container-narrow">
    <div class="wp-faq">
        @foreach($c['items'] ?? [] as $item)
            @include('sections.faq._item', ['item' => $item])
        @endforeach
    </div>
</x-site.section-shell>
