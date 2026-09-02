<?php

namespace Webparaguay\Provisioning\Whmcs;

use GuzzleHttp\Client;
use Webparaguay\Provisioning\DomainOutcome;
use Webparaguay\Provisioning\DomainRegistrar;
use Webparaguay\Provisioning\DomainRequest;
use Webparaguay\Provisioning\ProvisioningException;

/**
 * gTLD (.com, .net, ...) por WHMCS: automático.
 * `.com.py` por NIC.py: NO tiene API. El registro es por formulario y el pago
 * por transferencia o boca de pago. Se modela como estado del proyecto:
 * el sitio queda vivo en el subdominio y el dominio "en trámite" (24-72 h),
 * con tarea en el back-office. Al completarse se reapunta.
 */
final class WhmcsDomainRegistrar implements DomainRegistrar
{
    public function __construct(
        private string $baseUrl,
        private string $identifier,
        private string $secret,
        private ?Client $http = null,
    ) {
        $this->http ??= new Client(['base_uri' => rtrim($baseUrl, '/').'/', 'timeout' => 30]);
    }

    public function provision(string $customerRef, DomainRequest $request, string $subdomainFqdn): DomainOutcome
    {
        return match ($request->kind) {
            DomainRequest::SUBDOMAIN => new DomainOutcome(DomainOutcome::SUBDOMAIN_LIVE, $subdomainFqdn),

            DomainRequest::COMPY => new DomainOutcome(
                DomainOutcome::COMPY_PENDING,
                liveFqdn: $subdomainFqdn,
                pendingFqdn: $request->value,
                backofficeNote: "Registrar {$request->value} en NIC.py: formulario + transferencia/boca de pago. "
                    .'Al confirmarse, reapuntar el dominio a la instancia.',
            ),

            DomainRequest::GTLD => $this->registerGtld($customerRef, $request->value),

            default => throw new ProvisioningException("Tipo de dominio desconocido: {$request->kind}"),
        };
    }

    private function registerGtld(string $customerRef, string $domain): DomainOutcome
    {
        [$sld, $tld] = array_pad(explode('.', $domain, 2), 2, '');

        $res = $this->call('DomainRegister', [
            'clientid' => $customerRef,
            'domain' => $domain,
            'regperiod' => 1,
        ]);

        if (($res['result'] ?? null) !== 'success') {
            throw new ProvisioningException("WHMCS DomainRegister falló para {$domain}: ".($res['message'] ?? ''));
        }

        return new DomainOutcome(DomainOutcome::GTLD_LIVE, $domain);
    }

    /** @param array<string,mixed> $params @return array<string,mixed> */
    private function call(string $action, array $params): array
    {
        $response = $this->http->post('includes/api.php', [
            'form_params' => $params + [
                'identifier' => $this->identifier,
                'secret' => $this->secret,
                'action' => $action,
                'responsetype' => 'json',
            ],
        ]);

        return json_decode((string) $response->getBody(), true) ?? [];
    }
}
