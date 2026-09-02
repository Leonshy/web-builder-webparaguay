@props(['ctx', 'section', 'center' => false, 'heading' => true, 'container' => true, 'inner' => 'wp-container'])
@php
    $bg = $section->background();
    $bgImage = $bg === 'image' ? $section->backgroundImage() : null;
    $hasHeading = $heading && ($section->label() || $section->title() || $section->subtitle());
@endphp
<section id="{{ $section->domId() }}"
    @class([
        'wp-section',
        'wp-section--muted' => $bg === 'muted',
        'wp-section--dark' => $bg === 'dark',
        'wp-section--image' => $bg === 'image',
    ])
    @if($bgImage && !empty($bgImage['src'])) style="background-image:url('{{ $bgImage['src'] }}')" @endif>
    <div @class([$inner => $container])>
        @if($hasHeading)
            <div @class(['wp-heading', 'wp-heading--center' => $center]) style="margin-bottom:2.5rem">
                @if($section->label())<span class="wp-eyebrow">{{ $section->label() }}</span>@endif
                @if($section->title())<h2 class="wp-title">{{ $section->title() }}</h2>@endif
                @if($section->subtitle())<p class="wp-subtitle">{{ $section->subtitle() }}</p>@endif
            </div>
        @endif
        {{ $slot }}
    </div>
</section>
