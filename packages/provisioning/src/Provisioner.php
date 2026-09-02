<?php

namespace Webparaguay\Provisioning;

/**
 * Orquesta la publicación estándar:
 *
 *   cobro → alta de hosting → deploy del paquete versionado → dominio
 *
 * "Publicar = comprar": si el cobro no pasa, no se aprovisiona nada.
 * El `.com.py` no es un error: el sitio queda vivo en el subdominio y el
 * dominio en trámite (estado del proyecto).
 */
final class Provisioner implements SitePublisher
{
    public function __construct(
        private BillingGateway $billing,
        private HostingProvisioner $hosting,
        private DomainRegistrar $domains,
        private string $runtimeVersion,
    ) {}

    public function publish(PublishInput $input): PublishResult
    {
        $customerRef = $this->billing->ensureCustomer($input->customerName, $input->customerEmail);

        $charge = $this->billing->charge(
            $customerRef,
            $input->plan->price,
            "Publicación de {$input->siteName} — plan {$input->plan->label}",
        );

        if (! $charge->paid()) {
            throw new ProvisioningException("El cobro no se completó: {$charge->failureReason}");
        }

        $account = $this->hosting->createAccount($customerRef, $input->plan, $input->subdomainLabel);

        $this->hosting->deploySiteRuntime($account->accountRef, $input->siteRef, $this->runtimeVersion);

        $this->hosting->attachDomain($account->accountRef, $account->subdomainFqdn);

        $domain = $this->domains->provision($customerRef, $input->domain, $account->subdomainFqdn);

        if ($domain->status === DomainOutcome::GTLD_LIVE) {
            $this->hosting->attachDomain($account->accountRef, $domain->liveFqdn);
        }

        return new PublishResult($charge, $account, $domain, $this->runtimeVersion);
    }
}
