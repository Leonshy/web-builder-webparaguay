<?php

namespace App\Publishing;

use App\Generation\SiteRuntimeClient;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webparaguay\Provisioning\Charge;
use Webparaguay\Provisioning\DomainOutcome;
use Webparaguay\Provisioning\DomainRequest;
use Webparaguay\Provisioning\PublishInput;
use Webparaguay\Provisioning\SitePublisher;

/**
 * Publica un proyecto ya generado: cobra/ordena, aprovisiona hosting, deja el
 * paquete versionado desplegado y configura el dominio.
 *
 * Si el cobro queda PENDIENTE (WHMCS en modo manual): se registra la orden,
 * el proyecto pasa a `awaiting_payment` y se abre una tarea de back-office.
 * El sitio se activa con `builder:activate-order` cuando el pago se confirma.
 *
 * El .com.py no bloquea: sitio vivo en el subdominio, dominio en trámite.
 */
final class PublishSite
{
    public function __construct(
        private SitePublisher $publisher,
        private SiteRuntimeClient $runtime,
    ) {}

    public function handle(Project $project, string $planCode, string $domainKind, ?string $domainValue): void
    {
        $site = $project->site;
        abort_unless($site && $site->runtime_site_ref, 422, 'El sitio todavía no se generó.');
        abort_if(in_array($project->status, ['published', 'awaiting_payment'], true), 422, 'La publicación ya está en curso.');
        abort_unless($project->organization->billingComplete(), 422, 'Faltan los datos de facturación.');

        $plan = Plans::get($planCode);
        $label = $this->subdomainLabel($project);

        $result = $this->publisher->publish(new PublishInput(
            customerName: $project->user->name,
            customerEmail: $project->user->email,
            siteRef: $site->runtime_site_ref,
            siteName: $site->name,
            plan: $plan,
            domain: new DomainRequest($domainKind, $domainValue ?: $label),
            subdomainLabel: $label,
            billing: $project->organization->billingProfile(),
        ));

        $awaiting = $result->awaitingActivation || $result->charge->pending();

        DB::transaction(function () use ($project, $site, $result, $plan, $awaiting) {
            $project->payments()->create([
                'organization_id' => $project->organization_id,
                'concept' => "Publicación de {$site->name} — plan {$plan->label}",
                'amount' => $result->charge->amount->amount,
                'currency' => $result->charge->amount->currency,
                'status' => $awaiting ? Charge::PENDING : Charge::PAID,
                'gateway_ref' => $result->charge->gatewayRef,
                'gateway' => config('publishing.billing_driver'),
                'paid_at' => $awaiting ? null : now(),
            ]);

            $site->update([
                'hosting_account_ref' => $result->account->accountRef,
                'whmcs_order_ref' => $result->orderRef,
                'whmcs_service_ref' => $result->serviceRef,
                'runtime_version' => $result->runtimeVersion,
                'live_fqdn' => $result->domain->liveFqdn,
                'domain_status' => $result->domain->status,
                'pending_fqdn' => $result->domain->pendingFqdn,
                'published_domain' => $awaiting ? null : $result->domain->liveFqdn,
                'published_at' => $awaiting ? null : now(),
            ]);

            $project->update(['status' => $awaiting ? 'awaiting_payment' : 'published']);

            if ($awaiting) {
                $project->backofficeTasks()->create([
                    'kind' => 'confirm_order_payment',
                    'note' => $result->charge->note
                        ?? "Confirmar el pago de la orden #{$result->orderRef} en WHMCS y correr builder:activate-order {$project->id}.",
                ]);
            }

            if ($result->domain->status === DomainOutcome::COMPY_PENDING && ! $awaiting) {
                $project->backofficeTasks()->create([
                    'kind' => 'domain_compy_register',
                    'note' => $result->domain->backofficeNote ?? 'Registrar .com.py en NIC.py y reapuntar.',
                ]);
            }
        });

        if (! $awaiting) {
            $this->runtime->markPublished($site->runtime_site_ref, $result->domain->liveFqdn);
        }
    }

    private function subdomainLabel(Project $project): string
    {
        $base = Str::slug($project->site->name ?: $project->name);
        $base = Str::of($base)->replace('-', '')->limit(20, '')->toString() ?: 'sitio';

        return $base.$project->id;
    }
}
