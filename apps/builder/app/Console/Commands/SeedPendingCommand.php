<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Publishing\SeedInstance;
use Illuminate\Console\Command;

/**
 * Reintenta sembrar los sitios cuya instancia de Plesk todavía se estaba
 * aprovisionando (BD / git / SSL) al momento de publicar. Corre por el
 * scheduler cada minuto; en cuanto la instancia responde, el sitio queda
 * publicado sin intervención.
 */
class SeedPendingCommand extends Command
{
    protected $signature = 'builder:seed-pending';

    protected $description = 'Siembra los sitios publicados cuya instancia ya está lista';

    public function handle(SeedInstance $seeder): int
    {
        $pending = Project::where('status', 'awaiting_payment')
            ->whereHas('backofficeTasks', function ($q) {
                $q->whereIn('kind', ['seed_instance', 'configure_instance'])->where('status', 'open');
            })
            ->with('site')
            ->get();

        if ($pending->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($pending as $project) {
            $ok = $seeder->run($project);
            $this->components->{$ok ? 'info' : 'warn'}(
                "Proyecto {$project->id} ({$project->site?->live_fqdn}): ".($ok ? 'publicado' : 'instancia aún no lista'),
            );
        }

        return self::SUCCESS;
    }
}
