@php
    $c = $content;
    $button = $ctx->button($c['button'] ?? null);
    $items = $c['items'] ?? [];
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section" inner="wp-container wp-container-narrow">
    <div class="wp-prose">{!! $ctx->richtext($c['body'] ?? '') !!}</div>
    @if(count($items))
        <div class="wp-mediatext__points">
            @foreach($items as $item)
                <div class="wp-check">
                    <x-site.icon name="check" />
                    <div>
                        <strong>{{ $item['title'] }}</strong>
                        @if(!empty($item['description']))<p class="wp-item__desc">{{ $item['description'] }}</p>@endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    @if($button)<div class="wp-actions"><x-site.button :ctx="$ctx" :button="$button" /></div>@endif
</x-site.section-shell>
