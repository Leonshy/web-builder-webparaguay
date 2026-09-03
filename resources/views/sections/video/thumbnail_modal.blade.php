@php
    $c = $content;
    $video = \App\Rendering\VideoEmbed::parse($c['video_url'] ?? null);
    $thumb = $c['thumbnail'] ?? ($video && $video['thumbnail'] ? ['src' => $video['thumbnail'], 'alt' => $c['caption'] ?? 'Miniatura del video'] : null);
@endphp
@if($video)
<x-site.section-shell :ctx="$ctx" :section="$section" inner="wp-container wp-container-narrow">
    <a class="wp-video wp-video--thumb" href="{{ $video['watch_url'] }}" target="_blank" rel="noopener noreferrer"
       data-wp-video-embed="{{ $video['embed_url'] }}" aria-label="Reproducir el video">
        @if($thumb)<x-site.image :image="$thumb" loading="lazy" />@endif
        <span class="wp-video__play"><x-site.icon name="video" /></span>
    </a>
    @if(!empty($c['caption']))<p class="wp-item__desc" style="margin-top:0.75rem;text-align:center">{{ $c['caption'] }}</p>@endif
</x-site.section-shell>
@elseif(config('app.debug'))
<section class="wp-section wp-container"><p><strong>video:</strong> la URL no parsea. La sección no se renderiza.</p></section>
@endif
