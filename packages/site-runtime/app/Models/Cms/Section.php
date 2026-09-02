<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    protected $fillable = [
        'page_id', 'type', 'variant', 'position', 'is_active',
        'anchor', 'label', 'title', 'subtitle', 'background', 'background_image', 'content',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'background_image' => 'array',
        'content' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
