<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'site_id', 'slug', 'title', 'is_home', 'is_active', 'show_in_nav', 'position', 'seo',
    ];

    protected $casts = [
        'is_home' => 'boolean',
        'is_active' => 'boolean',
        'show_in_nav' => 'boolean',
        'seo' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('position');
    }
}
