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
    <div class="wp-prose wp-richtext">{!! $ctx->richtext($c['body'] ?? '') !!}</div>
</x-site.section-shell>
