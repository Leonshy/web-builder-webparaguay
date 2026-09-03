@php($c = $content)
<x-site.section-shell :ctx="$ctx" :section="$section" center>
    <div class="wp-grid wp-grid--{{ min(count($c['items'] ?? []), 3) ?: 1 }}">
        @foreach($c['items'] ?? [] as $item)
            <div class="wp-card">@include('sections.testimonials._card', ['item' => $item])</div>
        @endforeach
    </div>
</x-site.section-shell>
