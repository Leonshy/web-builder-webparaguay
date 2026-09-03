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
        private string $paymentMethod = 'banktransfer',      // gateway de WHMCS (mailin = transferencia)
        private int $taxIdFieldId = 0,                 // id del campo personalizado "RUC o CI"
        private int $companyFieldId = 0,               // id del campo personalizado "Razón social"
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

        $this->http ??= new Client(['base_uri' => rtrim($baseUrl, '/').'/', 'timeout' => 45, 'http_errors' => false]);
    }

    public function publish(PublishInput $input): PublishResult
    {
        $clientId = $this->ensureClient($input->customerName, $input->customerEmail, $input->billing);
        $fqdn = "{$input->subdomainLabel}.{$this->subdomainBase}";

        $order = $this->addOrder($clientId, $fqdn);
        $orderId = (string) $order['orderid'];
        $invoiceId = (string) ($order['invoiceid'] ?? '');
        $serviceId = (string) ($order['productids'] ?? $order['serviceids'] ?? '');

        $account = new HostingAccount($serviceId, $fqdn);
        $subdomain = new DomainOutcome(DomainOutcome::SUBDOMAIN_LIVE, $fqdn);

        if ($this->paymentMode === 'auto') {
            // Se asume el pago cobrado aparte (Bancard). Se registra en la
            // factura y se acepta la orden con auto-setup (crea customer +
            // suscripción en Plesk).
            if ($invoiceId === '') {
                throw new ProvisioningException('WHMCS no devolvió una factura para la orden.');
            }
            $this->call('AddInvoicePayment', [
                'invoiceid' => $invoiceId,
                'transid' => 'builder-'.$input->siteRef.'-'.time(),
                'gateway' => $this->paymentMethod,
            ]);
            $this->finalizeOrder($input->customerEmail, $orderId, $serviceId);

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

    /**
     * Acepta la orden con auto-setup (dispara el módulo Plesk: crea el customer
     * y la suscripción, igual que hacerlo a mano en el admin) y espera a que el
     * servicio quede Active. Idempotente: si ya está aceptada/activa, no falla.
     */
    public function finalizeOrder(string $clientEmail, string $orderId, string $serviceId): void
    {
        $clientId = $this->findClientId($clientEmail);
        if ($clientId === null) {
            throw new ProvisioningException("No se encontró el cliente {$clientEmail} en WHMCS.");
        }

        if ($this->serviceStatus($clientId, $serviceId) !== 'Active') {
            $res = $this->call('AcceptOrder', [
                'orderid' => $orderId,
                'autosetup' => true,
                'sendemail' => false,
            ]);
            // "Order Already Active/Accepted" no es un error real.
            if (($res['result'] ?? null) !== 'success'
                && ! str_contains((string) ($res['message'] ?? ''), 'Already')) {
                throw new ProvisioningException('WHMCS AcceptOrder falló: '.($res['message'] ?? 'desconocido'));
            }
        }

        $this->waitUntilActive($clientId, $serviceId);
    }

    /** @param array<string,string> $billing */
    private function ensureClient(string $name, string $email, array $billing = []): string
    {
        if ($id = $this->findClientId($email)) {
            return $id;
        }

        [$first, $last] = array_pad(explode(' ', trim($name), 2), 2, '');
        // WHMCS sólo acepta dígitos y espacios en el teléfono.
        $phone = isset($billing['phone'])
            ? trim(preg_replace('/[^\d ]+/', '', $billing['phone']))
            : null;

        $customFields = [];
        if ($this->taxIdFieldId > 0 && ! empty($billing['tax_id'])) {
            $customFields[$this->taxIdFieldId] = $billing['tax_id'];
        }
        if ($this->companyFieldId > 0 && ! empty($billing['companyname'])) {
            $customFields[$this->companyFieldId] = $billing['companyname'];
        }

        $res = $this->call('AddClient', array_filter([
            'firstname' => $first ?: $name,
            'lastname' => $last ?: '-',
            'companyname' => $billing['companyname'] ?? null,
            'email' => $email,
            // WHMCS exige contraseña aunque skipvalidation esté activo. El
            // cliente opera desde el builder; si necesita WHMCS, usa "olvidé
            // mi contraseña".
            'password2' => bin2hex(random_bytes(16)).'Aa1!',
            'phonenumber' => $phone ?: null,
            'address1' => $billing['address'] ?? null,
            'city' => $billing['city'] ?? null,
            'state' => $billing['state'] ?? ($billing['city'] ?? null),
            'postcode' => $billing['postcode'] ?? null,
            'country' => $billing['country'] ?? 'PY',
            'tax_id' => $billing['tax_id'] ?? null,
            'customfields' => $customFields !== [] ? base64_encode(serialize($customFields)) : null,
            // No forzar campos obligatorios que no aplican a una creación por API
            // (ej. "¿Cómo nos encontró?"). Los datos reales igual se envían arriba.
            'skipvalidation' => true,
            'noemail' => true,
        ], fn ($v) => $v !== null && $v !== ''));

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
