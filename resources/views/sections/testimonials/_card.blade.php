<figure class="wp-quote">
    @if(!empty($item['rating']))
        <div class="wp-quote__rating" aria-label="{{ $item['rating'] }} de 5">
            @for($i = 0; $i < (int) $item['rating']; $i++)<x-site.icon name="star" />@endfor
        </div>
    @endif
    <blockquote class="wp-quote__text">{{ $item['quote'] }}</blockquote>
    @if(!empty($item['author']) || !empty($item['role']))
        <figcaption class="wp-quote__by">
            @if(!empty($item['image']))<x-site.image :image="$item['image']" class="wp-quote__avatar" />@endif
            <span>
                @if(!empty($item['author']))<strong>{{ $item['author'] }}</strong>@endif
                @if(!empty($item['role']))<span class="wp-item__desc">{{ $item['role'] }}</span>@endif
            </span>
        </figcaption>
    @endif
</figure>
