<?php

namespace App\Publishing;

use App\Generation\SiteRuntimeClient;
use App\Models\BackofficeTask;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Webparaguay\Provisioning\HostingProvisioner;

/**
 * Cierra el trámite de un `.com.py`: cuando el back-office registró el dominio
 * en NIC.py, esto lo reapunta a la instancia y pone el sitio en línea con su
 * dominio propio. La tarea de back-office queda cerrada.
 */
final class CompleteCompyDomain
{
    public function __construct(
        private HostingProvisioner $hosting,
        private SiteRuntimeClient $runtime,
    ) {}

    public function handle(BackofficeTask $task): void
    {
        if ($task->kind !== 'domain_compy_register' || $task->status !== 'open') {
            throw new RuntimeException('La tarea no es un trámite de .com.py abierto.');
        }

        $site = $task->project->site;
        if (! $site || ! $site->pending_fqdn || ! $site->hosting_account_ref) {
            throw new RuntimeException('El sitio no tiene un .com.py en trámite.');
        }

        $fqdn = $site->pending_fqdn;

        $this->hosting->attachDomain($site->hosting_account_ref, $fqdn);

        DB::transaction(function () use ($task, $site, $fqdn) {
            $site->update([
                'live_fqdn' => $fqdn,
                'published_domain' => $fqdn,
                'domain_status' => 'compy_live',
                'pending_fqdn' => null,
            ]);
            $task->update(['status' => 'done']);
        });

        $this->runtime->markPublished($site->runtime_site_ref, $fqdn);
    }
}
