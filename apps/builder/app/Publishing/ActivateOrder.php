<?php

namespace App\Publishing;

use App\Models\Project;
use RuntimeException;
use Webparaguay\Provisioning\Whmcs\WhmcsProvisioner;

/**
 * Termina una publicación que quedó en `awaiting_payment`: acepta la orden en
 * WHMCS (auto-setup) si hace falta, aprovisiona la instancia de Plesk y siembra
 * el sitio. Reenvía el documento ya generado, no regenera.
 *
 * Si la instancia todavía no responde, `builder:seed-pending` lo reintenta solo.
 */
final class ActivateOrder
{
    public function __construct(private SeedInstance $seeder) {}

    public function handle(Project $project): void
    {
        abort_unless($project->status === 'awaiting_payment', 422, 'El proyecto no está esperando activación.');

        $site = $project->site;
        abort_unless($site && $site->document, 422, 'No hay documento generado para sembrar en la instancia.');

        // Con WHMCS: aceptar la orden (auto-setup) y esperar a que Plesk cree la
        // suscripción.
        if (config('publishing.hosting_driver') === 'whmcs') {
            app(WhmcsProvisioner::class)->finalizeOrder(
                $project->user->email,
                (string) $site->whmcs_order_ref,
                (string) $site->whmcs_service_ref,
            );
        }

        if (! $this->seeder->run($project->refresh())) {
            throw new RuntimeException(
                "La instancia {$site->live_fqdn} todavía se está aprovisionando (BD / git / SSL). "
                .'Se reintenta solo; volvé a correr esto en un minuto si querés forzarlo.'
            );
        }
    }
}
