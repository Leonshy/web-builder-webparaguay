@php($video = \App\Rendering\VideoEmbed::parse($item['video_url'] ?? null))
<figure class="wp-gallery__item">
    @if($video)
        <a href="{{ $video['watch_url'] }}" target="_blank" rel="noopener noreferrer" class="wp-gallery__play">
            <x-site.image :image="$item['image']" />
            <span class="wp-gallery__play-icon"><x-site.icon name="video" /></span>
        </a>
    @elseif(($lightbox ?? false) && !empty($item['image']['src']))
        <a href="{{ $item['image']['src'] }}" class="wp-gallery__link"><x-site.image :image="$item['image']" /></a>
    @else
        <x-site.image :image="$item['image']" />
    @endif
    @if(!empty($item['caption']))<figcaption>{{ $item['caption'] }}</figcaption>@endif
</figure>
