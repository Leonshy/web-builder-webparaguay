<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['project_id', 'organization_id', 'concept', 'amount', 'currency', 'status', 'gateway_ref', 'gateway', 'paid_at'];

    protected $casts = ['paid_at' => 'datetime'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
