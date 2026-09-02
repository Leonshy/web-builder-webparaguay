<?php

namespace Webparaguay\Provisioning\Whmcs;

use GuzzleHttp\Client;
use Webparaguay\Provisioning\Charge;
use Webparaguay\Provisioning\DomainOutcome;
use Webparaguay\Provisioning\HostingAccount;
use Webparaguay\Provisioning\ProvisioningException;
use Webparaguay\Provisioning\PublishInput;
use Webparaguay\Provisioning\PublishResult;
use Webparaguay\Provisioning\SitePublisher;

/**
 * Publicación nativa de WHMCS: se coloca una orden de un producto de hosting
 * ya configurado (pid) y WHMCS provisiona en Plesk (módulo de servidor).
 * El sitio es <label>.<subdomainBase>.
 *
 * Modos de pago:
 *  - manual: se crea la orden con factura impaga. Un admin confirma el pago en
 *    WHMCS y acepta la orden. `builder:activate-order` termina la publicación.
 *  - auto: se asume que el pago ya se cobró (Bancard, aparte); se registra el
 *    pago en la factura y se acepta la orden en el acto.
 *
 * Credenciales por env, con lista blanca de IP. No se ejercita en CI.
 */
final class WhmcsProvisioner implements SitePublisher
{
    public function __construct(
        private string $baseUrl,
        private string $identifier,
        private string $secret,
        private int $productId,
        private string $subdomainBase,
        private string $runtimeVersion,
        private string $paymentMode = 'manual',       // manual | auto
        private string $billingCycle = 'monthly',
        private string $paymentMethod = 'mailin',      // gateway de WHMCS (mailin = transferencia)
        private int $pollAttempts = 20,
        private int $pollSleepSeconds = 6,
        private ?Client $http = null,
    ) {
        if ($this->baseUrl === '' || $this->identifier === '' || $this->secret === '') {
            throw new ProvisioningException('WHMCS no está configurado (baseUrl / identifier / secret).');
        }
        if ($this->productId <= 0) {
            throw new ProvisioningException('Falta WHMCS_PRODUCT_ID (el pid del producto de hosting).');
        }

        $this->http ??= new Client(['base_uri' => rtrim($baseUrl, '/').'/', 'timeout' => 45]);
    }

    public function publish(PublishInput $input): PublishResult
    {
        $clientId = $this->ensureClient($input->customerName, $input->customerEmail);
        $fqdn = "{$input->subdomainLabel}.{$this->subdomainBase}";

        $order = $this->addOrder($clientId, $fqdn);
        $orderId = (string) $order['orderid'];
        $invoiceId = (string) ($order['invoiceid'] ?? '');
        $serviceId = (string) ($order['productids'] ?? $order['serviceids'] ?? '');

        $account = new HostingAccount($serviceId, $fqdn);
        $subdomain = new DomainOutcome(DomainOutcome::SUBDOMAIN_LIVE, $fqdn);

        if ($this->paymentMode === 'auto') {
            // El producto está en modo "On Payment": registrar el pago dispara
            // el aprovisionamiento en Plesk. No hace falta AcceptOrder.
            if ($invoiceId === '') {
                throw new ProvisioningException('WHMCS no devolvió una factura para la orden.');
            }
            $this->call('AddInvoicePayment', [
                'invoiceid' => $invoiceId,
                'transid' => 'builder-'.$input->siteRef.'-'.time(),
                'gateway' => $this->paymentMethod,
            ]);
            $this->waitUntilActive($clientId, $serviceId);

            return new PublishResult(
                charge: new Charge(Charge::PAID, $invoiceId, $input->plan->price),
                account: $account,
                domain: $subdomain,
                runtimeVersion: $this->runtimeVersion,
                orderRef: $orderId,
                serviceRef: $serviceId,
            );
        }

        // manual: la orden queda pendiente de pago.
        return new PublishResult(
            charge: new Charge(
                Charge::PENDING, $invoiceId, $input->plan->price,
                note: "Confirmar el pago de la factura #{$invoiceId} y aceptar la orden #{$orderId} en WHMCS, "
                    .'después correr `php artisan builder:activate-order`.',
            ),
            account: $account,
            domain: $subdomain,
            runtimeVersion: $this->runtimeVersion,
            orderRef: $orderId,
            serviceRef: $serviceId,
            awaitingActivation: true,
        );
    }

    /** ¿El servicio ya está Active? Lo usa builder:activate-order tras el pago. */
    public function serviceIsActive(string $clientEmail, string $serviceId): bool
    {
        $clientId = $this->findClientId($clientEmail);

        return $clientId !== null && $this->serviceStatus($clientId, $serviceId) === 'Active';
    }

    private function ensureClient(string $name, string $email): string
    {
        if ($id = $this->findClientId($email)) {
            return $id;
        }

        [$first, $last] = array_pad(explode(' ', trim($name), 2), 2, '');
        $res = $this->call('AddClient', [
            'firstname' => $first ?: $name,
            'lastname' => $last ?: '-',
            'email' => $email,
            'country' => 'PY',
            'noemail' => true,
        ]);

        if (($res['result'] ?? null) !== 'success') {
            throw new ProvisioningException('WHMCS AddClient falló: '.($res['message'] ?? 'desconocido'));
        }

        return (string) $res['clientid'];
    }

    private function findClientId(string $email): ?string
    {
        $res = $this->call('GetClientsDetails', ['email' => $email, 'stats' => false]);

        return ($res['result'] ?? null) === 'success' && ! empty($res['client']['id'])
            ? (string) $res['client']['id']
            : null;
    }

    /** @return array<string,mixed> */
    private function addOrder(string $clientId, string $fqdn): array
    {
        $res = $this->call('AddOrder', [
            'clientid' => $clientId,
            'pid' => [$this->productId],
            'domain' => [$fqdn],
            'billingcycle' => [$this->billingCycle],
            'paymentmethod' => $this->paymentMethod,
            'noinvoiceemail' => true,
        ]);

        if (($res['result'] ?? null) !== 'success') {
            throw new ProvisioningException('WHMCS AddOrder falló: '.($res['message'] ?? 'desconocido'));
        }

        return $res;
    }

    private function waitUntilActive(string $clientId, string $serviceId): void
    {
        for ($i = 0; $i < $this->pollAttempts; $i++) {
            if ($this->serviceStatus($clientId, $serviceId) === 'Active') {
                return;
            }
            sleep($this->pollSleepSeconds);
        }

        throw new ProvisioningException("El servicio #{$serviceId} no llegó a Active tras el aprovisionamiento.");
    }

    private function serviceStatus(string $clientId, string $serviceId): ?string
    {
        $res = $this->call('GetClientsProducts', ['clientid' => $clientId, 'serviceid' => $serviceId]);

        return $res['products']['product'][0]['status'] ?? null;
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
