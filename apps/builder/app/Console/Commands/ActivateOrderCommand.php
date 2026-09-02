<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Publishing\ActivateOrder;
use Illuminate\Console\Command;

/**
 * Termina una publicación en `awaiting_payment` (WHMCS modo manual), después de
 * confirmar el pago y aceptar la orden en WHMCS.
 *
 *   php artisan builder:activate-order {projectId}
 */
class ActivateOrderCommand extends Command
{
    protected $signature = 'builder:activate-order {project : id del proyecto}';

    protected $description = 'Activa una publicación con la orden ya pagada';

    public function handle(ActivateOrder $action): int
    {
        $project = Project::findOrFail($this->argument('project'));

        try {
            $action->handle($project);
        } catch (\Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Publicado: https://{$project->site->live_fqdn}");

        return self::SUCCESS;
    }
}
