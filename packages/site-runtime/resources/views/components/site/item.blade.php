@props(['ctx', 'item', 'iconType' => null, 'showIcon' => true, 'number' => null, 'inline' => false])
<div @class(['wp-item', 'wp-item--inline' => $inline])>
    @if($number !== null)
        <span class="wp-num">{{ $number }}</span>
    @elseif($showIcon)
        <span class="wp-icon-badge"><x-site.icon :name="$item['icon'] ?? null" :type="$iconType" /></span>
    @endif
    <div class="wp-item__body">
        @if(!empty($item['image']))
            <x-site.image :image="$item['image']" class="wp-media" />
        @endif
        @if(!empty($item['badge']))<span class="wp-badge">{{ $item['badge'] }}</span>@endif
        <h3 class="wp-item__title">{{ $item['title'] }}</h3>
        @if(!empty($item['description']))<p class="wp-item__desc">{{ $item['description'] }}</p>@endif
        @if(!empty($item['link']))
            <div class="wp-actions"><x-site.button :ctx="$ctx" :button="$item['link']" /></div>
        @endif
    </div>
</div>
