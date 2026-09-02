<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Enlace de preview: no indexable, compartible, opcionalmente con vencimiento.
 */
class PreviewToken extends Model
{
    protected $fillable = ['site_id', 'token', 'label', 'expires_at', 'last_viewed_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_viewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PreviewToken $token) {
            $token->token ??= Str::random(48);
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
