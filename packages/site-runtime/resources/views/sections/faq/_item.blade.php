<details class="wp-faq__item">
    <summary class="wp-faq__q">
        <span>{{ $item['question'] }}</span>
        <x-site.icon name="plus" class="wp-faq__icon" />
    </summary>
    <div class="wp-faq__a wp-prose">{!! $ctx->richtext($item['answer'] ?? '') !!}</div>
</details>
