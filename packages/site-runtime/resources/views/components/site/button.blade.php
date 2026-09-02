@props(['ctx', 'button' => null, 'block' => false])
@php
    $b = null;
    if (is_array($button)) {
        $b = array_key_exists('href', $button) ? $button : $ctx->button($button);
    }
@endphp
@if($b)
<a href="{{ $b['href'] }}"
   @class(['wp-btn', 'wp-btn--'.$b['style'], 'w-full' => $block])
   @if($b['target'] === '_blank') target="_blank" @endif
   @if($b['rel']) rel="{{ $b['rel'] }}" @endif>
    @if($b['icon'])<x-site.icon :name="$b['icon']" class="wp-btn__icon" />@endif
    <span>{{ $b['label'] }}</span>
</a>
@endif
