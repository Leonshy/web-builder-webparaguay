<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Site extends Model
{
    protected $fillable = ['project_id', 'runtime_site_ref', 'preview_url', 'name', 'published_domain', 'published_at', 'hosting_account_ref', 'whmcs_order_ref', 'whmcs_service_ref', 'runtime_version', 'live_fqdn', 'domain_status', 'pending_fqdn', 'document', 'cms_email', 'cms_password'];

    protected $casts = ['published_at' => 'datetime', 'document' => 'array'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
