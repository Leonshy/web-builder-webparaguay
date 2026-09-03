@php
    $c = $content;
    $width = $c['width'] ?? 'normal';
    $inner = match ($width) {
        'narrow' => 'wp-container wp-container-narrow',
        'wide' => 'wp-container',
        default => 'wp-container wp-richtext--normal',
    };
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section" :inner="$inner">
    <div class="wp-card wp-card--raised wp-prose wp-richtext" style="padding:2.5rem">{!! $ctx->richtext($c['body'] ?? '') !!}</div>
</x-site.section-shell>
