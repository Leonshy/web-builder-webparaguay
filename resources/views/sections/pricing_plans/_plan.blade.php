<article @class(['wp-plan', 'wp-plan--featured' => !empty($plan['is_featured'])])>
    @if(!empty($plan['is_featured']))<span class="wp-plan__flag">Recomendado</span>@endif
    <h3 class="wp-plan__name">{{ $plan['name'] }}</h3>
    @if(!empty($plan['tagline']))<p class="wp-item__desc">{{ $plan['tagline'] }}</p>@endif
    @if(!empty($plan['price']))
        <p class="wp-plan__price">{{ $plan['price'] }}@if(!empty($plan['period']))<span>/{{ $plan['period'] }}</span>@endif</p>
    @endif
    @if(!empty($plan['description']))<p class="wp-plan__desc">{{ $plan['description'] }}</p>@endif
    @if(!empty($plan['features']))
        <ul class="wp-plan__features">
            @foreach($plan['features'] as $feature)
                <li><x-site.icon name="check" /> <span>{{ $feature }}</span></li>
            @endforeach
        </ul>
    @endif
    @if(!empty($plan['button']))<div class="wp-actions"><x-site.button :ctx="$ctx" :button="$plan['button']" block /></div>@endif
</article>
