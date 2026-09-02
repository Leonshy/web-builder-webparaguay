@php
    $c = $content;
    $map = $ctx->settings()['map_embed_url'] ?? null;
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section">
    <div class="wp-contact wp-contact--split">
        <div>
            @include('sections.contact_form._form', ['content' => $c])
        </div>
        <div>
            @include('sections.contact_form._info', ['c' => $c])
            @if($map)
                <div class="wp-contact__map">
                    <iframe src="{{ $map }}" title="Ubicación en el mapa" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            @endif
        </div>
    </div>
</x-site.section-shell>
