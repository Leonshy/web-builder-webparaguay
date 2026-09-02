@php($s = $ctx->settings())
<div class="wp-contact-info">
    @if(!empty($c['box_title']))<h3 class="wp-item__title">{{ $c['box_title'] }}</h3>@endif
    @if(!empty($c['box_body']))<p class="wp-item__desc">{{ $c['box_body'] }}</p>@endif
    <ul class="wp-contact-info__list">
        @if(!empty($s['address']))<li><x-site.icon name="map-pin" /> <span>{{ $s['address'] }}</span></li>@endif
        @if(!empty($s['phone']))<li><x-site.icon name="phone" /> <a href="tel:+{{ preg_replace('/\D+/', '', $s['phone']) }}">{{ $s['phone'] }}</a></li>@endif
        @if(!empty($s['whatsapp']))<li><x-site.icon name="whatsapp" /> <a href="https://wa.me/{{ preg_replace('/\D+/', '', $s['whatsapp']) }}">{{ $s['whatsapp'] }}</a></li>@endif
        @if(!empty($s['email']))<li><x-site.icon name="mail" /> <a href="mailto:{{ $s['email'] }}">{{ $s['email'] }}</a></li>@endif
        @if(!empty($s['schedule']))<li><x-site.icon name="clock" /> <span>{{ $s['schedule'] }}</span></li>@endif
    </ul>
    @if(($c['show_socials'] ?? true) && !empty($s['socials']))
        <ul class="wp-contact-info__socials">
            @foreach($s['socials'] as $social)
                <li><a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer">{{ $social['label'] ?? ucfirst($social['platform']) }}</a></li>
            @endforeach
        </ul>
    @endif
</div>
