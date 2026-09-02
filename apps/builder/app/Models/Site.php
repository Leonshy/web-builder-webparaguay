<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Site extends Model
{
    protected $fillable = ['project_id', 'runtime_site_ref', 'preview_url', 'name', 'published_domain', 'published_at', 'hosting_account_ref', 'runtime_version', 'live_fqdn', 'domain_status', 'pending_fqdn'];

    protected $casts = ['published_at' => 'datetime'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
