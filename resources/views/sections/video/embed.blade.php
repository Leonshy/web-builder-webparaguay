@php
    $c = $content;
    $video = \App\Rendering\VideoEmbed::parse($c['video_url'] ?? null);
@endphp
@if($video)
<x-site.section-shell :ctx="$ctx" :section="$section" inner="wp-container wp-container-narrow">
    <div class="wp-video">
        <iframe src="{{ $video['embed_url'] }}" title="{{ $c['caption'] ?? $section->title() ?? 'Video' }}"
            loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
    </div>
    @if(!empty($c['caption']))<p class="wp-item__desc" style="margin-top:0.75rem;text-align:center">{{ $c['caption'] }}</p>@endif
</x-site.section-shell>
@elseif(config('app.debug'))
<section class="wp-section wp-container"><p><strong>video:</strong> la URL <code>{{ $c['video_url'] ?? '' }}</code> no parsea (sólo YouTube y Vimeo). La sección no se renderiza.</p></section>
@endif
