<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewDraft extends Model
{
    public const STAGES = ['welcome', 'brand', 'purpose', 'content', 'review', 'generating', 'done', 'needs_fix'];

    protected $fillable = [
        'project_id', 'stage',
        'brand_status', 'purpose_status', 'content_status',
        'brand', 'purpose', 'content', 'transcript',
        'palette_regenerations', 'last_error',
    ];

    protected $casts = [
        'brand' => 'array',
        'purpose' => 'array',
        'content' => 'array',
        'transcript' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function allConfirmed(): bool
    {
        return $this->brand_status === 'confirmed'
            && $this->purpose_status === 'confirmed'
            && $this->content_status === 'confirmed';
    }

    /** Reabrir y cambiar una etapa marca las siguientes para revisar. */
    public function flagLaterStages(string $changed): void
    {
        $order = ['brand', 'purpose', 'content'];
        $from = array_search($changed, $order, true);
        foreach (array_slice($order, $from + 1) as $stage) {
            if ($this->{"{$stage}_status"} === 'confirmed') {
                $this->{"{$stage}_status"} = 'needs_review';
            }
        }
    }
}
