@php($c = $content)
<x-site.section-shell :ctx="$ctx" :section="$section" inner="wp-container wp-container-narrow">
    <div class="wp-quote--single">
        @foreach($c['items'] ?? [] as $item)
            @include('sections.testimonials._card', ['item' => $item])
        @endforeach
    </div>
</x-site.section-shell>
