@php
    $c = $content;
    $button = $ctx->button($c['button'] ?? null);
    $items = $c['items'] ?? [];
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section">
    <div class="wp-mediatext wp-mediatext--split">
        @if(!empty($c['image']))
            <div class="wp-mediatext__media">
                <x-site.image :image="$c['image']" class="wp-media" />
            </div>
        @endif
        <div>
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
        </div>
    </div>
</x-site.section-shell>
