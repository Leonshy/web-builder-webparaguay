<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    protected $fillable = [
        'builder_project_ref', 'name', 'template', 'schema_version',
        'theme', 'settings', 'layout', 'published_domain', 'published_at',
    ];

    protected $casts = [
        'theme' => 'array',
        'settings' => 'array',
        'layout' => 'array',
        'published_at' => 'datetime',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class)->orderBy('position');
    }

    public function previewTokens(): HasMany
    {
        return $this->hasMany(PreviewToken::class);
    }
}
