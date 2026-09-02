<?php

namespace App\Console\Commands;

use App\Models\BackofficeTask;
use App\Publishing\CompleteCompyDomain;
use Illuminate\Console\Command;

/**
 * Back-office: cuando se registró un `.com.py` en NIC.py, esto lo reapunta a
 * la instancia y pone el sitio en línea con su dominio propio.
 *
 *   php artisan builder:complete-domain-task {taskId}
 */
class CompleteDomainTask extends Command
{
    protected $signature = 'builder:complete-domain-task {task : id de la backoffice_task}';

    protected $description = 'Cierra un trámite de .com.py y reapunta el dominio';

    public function handle(CompleteCompyDomain $action): int
    {
        $task = BackofficeTask::findOrFail($this->argument('task'));

        try {
            $action->handle($task);
        } catch (\Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Dominio {$task->project->site->live_fqdn} en línea. Tarea #{$task->id} cerrada.");

        return self::SUCCESS;
    }
}
