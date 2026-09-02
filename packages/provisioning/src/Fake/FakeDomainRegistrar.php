<?php

namespace Webparaguay\Provisioning\Fake;

use Webparaguay\Provisioning\DomainOutcome;
use Webparaguay\Provisioning\DomainRegistrar;
use Webparaguay\Provisioning\DomainRequest;

final class FakeDomainRegistrar implements DomainRegistrar
{
    public function provision(string $customerRef, DomainRequest $request, string $subdomainFqdn): DomainOutcome
    {
        return match ($request->kind) {
            DomainRequest::GTLD => new DomainOutcome(DomainOutcome::GTLD_LIVE, $request->value),
            DomainRequest::COMPY => new DomainOutcome(
                DomainOutcome::COMPY_PENDING,
                liveFqdn: $subdomainFqdn,
                pendingFqdn: $request->value,
                backofficeNote: 'Registrar '.$request->value.' en NIC.py (formulario + transferencia). Reapuntar al completarse.',
            ),
            default => new DomainOutcome(DomainOutcome::SUBDOMAIN_LIVE, $subdomainFqdn),
        };
    }
}
