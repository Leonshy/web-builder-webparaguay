<?php

namespace App\Publishing;

use App\Generation\SiteRuntimeClient;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Webparaguay\Provisioning\Whmcs\WhmcsProvisioner;

/**
 * Termina una publicación que quedó en `awaiting_payment` (WHMCS manual):
 * verifica que la orden esté activa, siembra el sitio en la instancia y lo
 * pone en línea. Reenvía el documento ya generado, no regenera.
 */
final class ActivateOrder
{
    public function __construct(private SiteRuntimeClient $runtime) {}

    public function handle(Project $project): void
    {
        abort_unless($project->status === 'awaiting_payment', 422, 'El proyecto no está esperando activación.');

        $site = $project->site;
        $fqdn = $site->live_fqdn;
        $instanceUrl = 'https://'.$fqdn;

        abort_unless($site->document, 422, 'No hay documento generado para sembrar en la instancia.');

        // Con WHMCS: confirmar que Plesk ya provisionó la suscripción.
        if (config('publishing.hosting_driver') === 'whmcs') {
            $whmcs = app(WhmcsProvisioner::class);
            if (! $whmcs->serviceIsActive($project->user->email, (string) $site->whmcs_service_ref)) {
                throw new RuntimeException("La orden #{$site->whmcs_order_ref} todavía no está activa en WHMCS.");
            }
        }

        $result = $this->runtime->createSite($project, $site->name, $site->document, $instanceUrl);

        DB::transaction(function () use ($project, $site, $fqdn, $result) {
            $site->update([
                'runtime_site_ref' => $result['site_ref'],
                'published_domain' => $fqdn,
                'published_at' => now(),
            ]);
            $project->payments()->where('status', 'pending')->update(['status' => 'paid', 'paid_at' => now()]);
            $project->backofficeTasks()->where('kind', 'confirm_order_payment')->where('status', 'open')->update(['status' => 'done']);
            $project->update(['status' => 'published']);
        });

        $this->runtime->markPublished($result['site_ref'], $fqdn, $instanceUrl);
    }
}
