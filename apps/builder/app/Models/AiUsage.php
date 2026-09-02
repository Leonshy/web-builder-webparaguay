<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsage extends Model
{
    protected $fillable = [
        'organization_id', 'project_id', 'action', 'model',
        'input_tokens', 'output_tokens', 'cost_microusd', 'occurred_at',
    ];

    protected $casts = ['occurred_at' => 'datetime'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function costUsd(): float
    {
        return $this->cost_microusd / 1_000_000;
    }
}
