<?php

namespace Webparaguay\Provisioning\Plesk;

use GuzzleHttp\Client;
use Webparaguay\Provisioning\HostingAccount;
use Webparaguay\Provisioning\HostingPlan;
use Webparaguay\Provisioning\HostingProvisioner;
use Webparaguay\Provisioning\ProvisioningException;

/**
 * Alta de hosting y despliegue vía la API REST de Plesk.
 *
 * El despliegue del CMS es POR GIT: se configura el repositorio del monorepo
 * apuntando al tag del paquete site-runtime y el servidor hace `git pull`.
 * Nunca se sube código a mano. Todas las instancias corren el MISMO tag.
 *
 * La API key de Plesk puede aprovisionar servidores: NUNCA en el repo, y con
 * lista blanca de IP obligatoria. No se ejercita en CI (default: Fake).
 */
final class PleskHostingProvisioner implements HostingProvisioner
{
    public function __construct(
        private string $baseUrl,
        private string $apiKey,
        private string $servicePlan,       // nombre del plan de hosting en Plesk
        private string $subdomainBase,     // p. ej. "webparaguay.com"
        private string $gitRepoUrl,        // git@github.com:Leonshy/web-builder-webparaguay.git
        private ?Client $http = null,
    ) {
        if ($this->baseUrl === '' || $this->apiKey === '') {
            throw new ProvisioningException('Plesk no está configurado (baseUrl / apiKey).');
        }

        $this->http ??= new Client([
            'base_uri' => rtrim($baseUrl, '/').'/api/v2/',
            'timeout' => 60,
            'headers' => ['X-API-Key' => $apiKey, 'Content-Type' => 'application/json'],
        ]);
    }

    public function createAccount(string $customerRef, HostingPlan $plan, string $subdomainLabel): HostingAccount
    {
        $fqdn = "{$subdomainLabel}.{$this->subdomainBase}";

        $res = $this->request('POST', 'domains', [
            'name' => $fqdn,
            'hosting_type' => 'virtual',
            'plan' => ['name' => $this->servicePlan],
        ]);

        return new HostingAccount((string) ($res['id'] ?? $fqdn), $fqdn);
    }

    public function deploySiteRuntime(string $accountRef, string $siteRef, string $version): void
    {
        // Configura el repositorio git en el subscription y despliega el tag.
        $this->request('POST', "domains/{$accountRef}/git/repositories", [
            'name' => 'site-runtime',
            'remote_url' => $this->gitRepoUrl,
            'deployment_path' => '/httpdocs',
            'deploy_mode' => 'auto',
            'branch' => "site-runtime-v{$version}",
        ]);

        // Pasa a la instancia qué sitio debe servir (ref en site-runtime).
        $this->request('POST', "domains/{$accountRef}/git/repositories/site-runtime/deploy", [
            'actions' => [
                "printf 'SITE_RUNTIME_SITE_REF={$siteRef}\\n' >> .env",
                'php artisan migrate --force',
                'php artisan config:cache',
            ],
        ]);
    }

    public function attachDomain(string $accountRef, string $fqdn): void
    {
        // Alias de dominio + DNS + Let's Encrypt.
        $this->request('POST', "domains/{$accountRef}/aliases", ['name' => $fqdn]);
        $this->request('POST', "domains/{$accountRef}/ssl-certificates/lets-encrypt", ['domain' => $fqdn]);
    }

    /** @param array<string,mixed> $body @return array<string,mixed> */
    private function request(string $method, string $path, array $body): array
    {
        $response = $this->http->request($method, $path, ['json' => $body]);

        if ($response->getStatusCode() >= 400) {
            throw new ProvisioningException("Plesk {$method} {$path} devolvió {$response->getStatusCode()}");
        }

        return json_decode((string) $response->getBody(), true) ?? [];
    }
}
