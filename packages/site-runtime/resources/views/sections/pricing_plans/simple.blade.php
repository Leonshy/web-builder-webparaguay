@php($c = $content)
<x-site.section-shell :ctx="$ctx" :section="$section" inner="wp-container wp-container-narrow">
    <div class="wp-plans wp-plans--simple">
        @foreach($c['items'] ?? [] as $plan)
            @include('sections.pricing_plans._plan', ['plan' => $plan])
        @endforeach
    </div>
    @if(!empty($c['footer_note']))<p class="wp-item__desc" style="margin-top:1.5rem">{{ $c['footer_note'] }}</p>@endif
</x-site.section-shell>
