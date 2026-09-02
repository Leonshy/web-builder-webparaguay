@php($c = $content)
<x-site.section-shell :ctx="$ctx" :section="$section" center>
    <div class="wp-grid wp-grid--{{ min(count($c['items'] ?? []), 4) ?: 1 }} wp-plans">
        @foreach($c['items'] ?? [] as $plan)
            @include('sections.pricing_plans._plan', ['plan' => $plan])
        @endforeach
    </div>
    @if(!empty($c['footer_note']))<p class="wp-item__desc" style="margin-top:1.5rem;text-align:center">{{ $c['footer_note'] }}</p>@endif
</x-site.section-shell>
