@props(['image' => null, 'class' => 'wp-media', 'loading' => 'lazy'])
@if($image && !empty($image['src']))
@php($focal = $image['focal'] ?? 'center')
<img src="{{ $image['src'] }}"
     alt="{{ $image['alt'] ?? '' }}"
     @class([$class, 'wp-media--'.$focal => $focal !== 'center'])
     loading="{{ $loading }}" decoding="async">
@endif
