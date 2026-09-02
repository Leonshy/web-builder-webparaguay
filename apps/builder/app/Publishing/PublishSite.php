<?php

namespace App\Publishing;

use App\Generation\SiteRuntimeClient;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webparaguay\Provisioning\Charge;
use Webparaguay\Provisioning\DomainOutcome;
use Webparaguay\Provisioning\DomainRequest;
use Webparaguay\Provisioning\Provisioner;
use Webparaguay\Provisioning\PublishInput;

/**
 * Publica un proyecto ya generado: cobra, aprovisiona hosting, despliega el
 * paquete versionado por git y configura el dominio.
 *
 * El .com.py no bloquea: el sitio queda vivo en el subdominio y el dominio
 * en trámite, con tarea en el back-office.
 */
final class PublishSite
{
    public function __construct(
        private Provisioner $provisioner,
        private SiteRuntimeClient $runtime,
    ) {}

    public function handle(Project $project, string $planCode, string $domainKind, ?string $domainValue): void
    {
        $site = $project->site;
        abort_unless($site && $site->runtime_site_ref, 422, 'El sitio todavía no se generó.');
        abort_if($project->status === 'published', 422, 'El sitio ya está publicado.');

        $plan = Plans::get($planCode);
        $label = $this->subdomainLabel($project);

        $result = $this->provisioner->publish(new PublishInput(
            customerName: $project->user->name,
            customerEmail: $project->user->email,
            siteRef: $site->runtime_site_ref,
            siteName: $site->name,
            plan: $plan,
            domain: new DomainRequest($domainKind, $domainValue ?: $label),
            subdomainLabel: $label,
        ));

        DB::transaction(function () use ($project, $site, $result, $plan) {
            $project->payments()->create([
                'organization_id' => $project->organization_id,
                'concept' => "Publicación de {$site->name} — plan {$plan->label}",
                'amount' => $result->charge->amount->amount,
                'currency' => $result->charge->amount->currency,
                'status' => Charge::PAID,
                'gateway_ref' => $result->charge->gatewayRef,
                'gateway' => config('publishing.billing_driver'),
                'paid_at' => now(),
            ]);

            $site->update([
                'hosting_account_ref' => $result->account->accountRef,
                'runtime_version' => $result->runtimeVersion,
                'live_fqdn' => $result->domain->liveFqdn,
                'domain_status' => $result->domain->status,
                'pending_fqdn' => $result->domain->pendingFqdn,
                'published_domain' => $result->domain->liveFqdn,
                'published_at' => now(),
            ]);

            $project->update(['status' => 'published']);

            if ($result->domain->status === DomainOutcome::COMPY_PENDING) {
                $project->backofficeTasks()->create([
                    'kind' => 'domain_compy_register',
                    'note' => $result->domain->backofficeNote ?? 'Registrar .com.py en NIC.py y reapuntar.',
                ]);
            }
        });

        $this->runtime->markPublished($site->runtime_site_ref, $result->domain->liveFqdn);
    }

    private function subdomainLabel(Project $project): string
    {
        $base = Str::slug($project->site->name ?: $project->name);
        $base = Str::of($base)->replace('-', '')->limit(20, '')->toString() ?: 'sitio';

        return $base.$project->id;
    }
}
