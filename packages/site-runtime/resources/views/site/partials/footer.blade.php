@php
    $settings = $ctx->settings();
    $footer = $ctx->site->layout()['footer'] ?? [];
    $socials = $settings['socials'] ?? [];
    $showSocials = $footer['show_socials'] ?? true;
    $year = date('Y');
    $copyright = $footer['copyright'] ?? ('© '.$year.' '.$ctx->site->businessName().'. Todos los derechos reservados.');
    $pages = array_filter($ctx->site->pages(), fn ($p) => $p->showInNav());
@endphp
<footer class="wp-footer">
    <div class="wp-container" style="display:grid;gap:2.5rem">
        <div style="display:grid;gap:2rem;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr))">
            <div style="max-width:22rem">
                <p style="font-family:var(--wp-font-heading);font-weight:var(--wp-weight-heading);font-size:var(--wp-text-lg);color:var(--wp-dark-text)">
                    {{ $ctx->site->businessName() }}
                </p>
                @if(!empty($footer['description']))
                    <p style="margin-top:0.6rem">{{ $footer['description'] }}</p>
                @endif
            </div>

            @if(count($pages) > 1)
                <nav aria-label="Páginas">
                    <p style="color:var(--wp-dark-text);font-weight:600;margin-bottom:0.75rem">Navegación</p>
                    <ul style="list-style:none;display:grid;gap:0.5rem">
                        @foreach($pages as $p)
                            <li><a href="{{ $ctx->url->pageHref($p) }}">{{ $p->title() }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            <div>
                <p style="color:var(--wp-dark-text);font-weight:600;margin-bottom:0.75rem">Contacto</p>
                <ul style="list-style:none;display:grid;gap:0.5rem">
                    @if(!empty($settings['address']))<li>{{ $settings['address'] }}</li>@endif
                    @if(!empty($settings['phone']))<li><a href="tel:+{{ preg_replace('/\D+/', '', $settings['phone']) }}">{{ $settings['phone'] }}</a></li>@endif
                    @if(!empty($settings['email']))<li><a href="mailto:{{ $settings['email'] }}">{{ $settings['email'] }}</a></li>@endif
                    @if(!empty($settings['schedule']))<li>{{ $settings['schedule'] }}</li>@endif
                </ul>

                @if($showSocials && count($socials))
                    <ul style="list-style:none;display:flex;gap:1rem;margin-top:1rem">
                        @foreach($socials as $social)
                            <li><a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer">{{ $social['label'] ?? ucfirst($social['platform']) }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <p style="border-top:1px solid var(--wp-dark-border);padding-top:1.5rem;font-size:var(--wp-text-sm)">{{ $copyright }}</p>
    </div>
</footer>
