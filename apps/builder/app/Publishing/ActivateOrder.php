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

        // Con WHMCS: aceptar la orden (auto-setup) y esperar a que Plesk
        // provisione la suscripción.
        if (config('publishing.hosting_driver') === 'whmcs') {
            app(WhmcsProvisioner::class)->finalizeOrder(
                $project->user->email,
                (string) $site->whmcs_order_ref,
                (string) $site->whmcs_service_ref,
            );
        }

        $cms = CmsCredentials::ensure($site);
        $result = $this->runtime->createSite(
            $project, $site->name, $site->document, $instanceUrl,
            $cms['email'], $cms['password'], $cms['name'],
        );

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
