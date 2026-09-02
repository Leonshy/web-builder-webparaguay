<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;

/**
 * Purga los proyectos draft inactivos (§5.9, control de abuso).
 * Un proyecto que nunca pasó de 'draft' y no se toca hace 30 días se elimina.
 */
class PurgeInactiveDrafts extends Command
{
    protected $signature = 'builder:purge-drafts {--days=30} {--dry-run}';

    protected $description = 'Elimina proyectos draft inactivos';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $stale = Project::where('status', 'draft')
            ->where('updated_at', '<', $cutoff)
            ->get();

        foreach ($stale as $project) {
            $this->line(($this->option('dry-run') ? '[dry-run] ' : '')."Purgando: {$project->name} (#{$project->id})");
            if (! $this->option('dry-run')) {
                $project->delete();
            }
        }

        $this->info("{$stale->count()} draft(s) inactivos.");

        return self::SUCCESS;
    }
}
