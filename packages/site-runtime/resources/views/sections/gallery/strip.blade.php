@php
    $c = $content;
    $items = $c['items'] ?? [];
    $lightbox = $c['lightbox'] ?? true;
    $button = $ctx->button($c['button'] ?? null);
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section">
    <div class="wp-gallery wp-gallery--strip" @if($lightbox) data-wp-lightbox @endif>
        @foreach($items as $item)
            @include('sections.gallery._item', ['item' => $item, 'lightbox' => $lightbox])
        @endforeach
    </div>
    @if($button)<div class="wp-actions" style="margin-top:2rem"><x-site.button :ctx="$ctx" :button="$button" /></div>@endif
</x-site.section-shell>
