@php
    $c = $content;
    $primary = $ctx->button($c['primary_button'] ?? null);
@endphp
<x-site.section-shell :ctx="$ctx" :section="$section" :heading="false">
    <div class="wp-cta">
        @if($section->label())<span class="wp-eyebrow">{{ $section->label() }}</span>@endif
        @if($section->title())<h2 class="wp-title" style="margin-top:0.5rem">{{ $section->title() }}</h2>@endif
        @if(!empty($c['body']))<p class="wp-cta__body">{{ $c['body'] }}</p>@endif
        @if($primary)<div class="wp-actions"><x-site.button :ctx="$ctx" :button="$primary" /></div>@endif
    </div>
</x-site.section-shell>
