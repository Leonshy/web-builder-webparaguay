@php
    $c = $content;
    $plans = $c['items'] ?? [];
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section">
    <div style="overflow-x:auto">
        <table class="wp-plan-table">
            <thead>
                <tr>
                    <th></th>
                    @foreach($plans as $plan)
                        <th @class(['wp-plan-table__featured' => !empty($plan['is_featured'])])>
                            {{ $plan['name'] }}
                            @if(!empty($plan['price']))<span class="wp-plan__price">{{ $plan['price'] }}@if(!empty($plan['period']))<span>/{{ $plan['period'] }}</span>@endif</span>@endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th scope="row">Incluye</th>
                    @foreach($plans as $plan)
                        <td>
                            <ul class="wp-plan__features">
                                @foreach($plan['features'] ?? [] as $feature)<li><x-site.icon name="check" /> <span>{{ $feature }}</span></li>@endforeach
                            </ul>
                            @if(!empty($plan['description']))<p class="wp-item__desc">{{ $plan['description'] }}</p>@endif
                        </td>
                    @endforeach
                </tr>
                <tr>
                    <td></td>
                    @foreach($plans as $plan)<td>@if(!empty($plan['button']))<x-site.button :ctx="$ctx" :button="$plan['button']" />@endif</td>@endforeach
                </tr>
            </tbody>
        </table>
    </div>
    @if(!empty($c['footer_note']))<p class="wp-item__desc" style="margin-top:1.5rem">{{ $c['footer_note'] }}</p>@endif
</x-site.section-shell>
