<?php

namespace Webparaguay\Provisioning\Whmcs;

use GuzzleHttp\Client;
use Webparaguay\Provisioning\BillingGateway;
use Webparaguay\Provisioning\Charge;
use Webparaguay\Provisioning\Money;
use Webparaguay\Provisioning\ProvisioningException;

/**
 * Cobro y alta de cliente vía la API de WHMCS.
 *
 * Las credenciales (identifier/secret) pueden crear clientes y cobrar:
 * NUNCA van en el repositorio. Llegan por config → env, con lista blanca de
 * IP obligatoria del lado de WHMCS (regla 17 del CLAUDE.md).
 *
 * No se ejercita en CI: el default es FakeBillingGateway.
 */
final class WhmcsBillingGateway implements BillingGateway
{
    public function __construct(
        private string $baseUrl,
        private string $identifier,
        private string $secret,
        private ?Client $http = null,
    ) {
        if ($this->baseUrl === '' || $this->identifier === '' || $this->secret === '') {
            throw new ProvisioningException('WHMCS no está configurado (baseUrl / identifier / secret).');
        }

        $this->http ??= new Client(['base_uri' => rtrim($baseUrl, '/').'/', 'timeout' => 30]);
    }

    public function ensureCustomer(string $name, string $email): string
    {
        $existing = $this->call('GetClientsDetails', ['email' => $email, 'stats' => false]);
        if (($existing['result'] ?? null) === 'success' && ! empty($existing['client']['id'])) {
            return (string) $existing['client']['id'];
        }

        [$first, $last] = array_pad(explode(' ', $name, 2), 2, '');
        $created = $this->call('AddClient', [
            'firstname' => $first ?: $name,
            'lastname' => $last ?: '-',
            'email' => $email,
            'country' => 'PY',
            'noemail' => true,
        ]);

        if (($created['result'] ?? null) !== 'success') {
            throw new ProvisioningException('WHMCS AddClient falló: '.($created['message'] ?? 'desconocido'));
        }

        return (string) $created['clientid'];
    }

    public function charge(string $customerRef, Money $amount, string $description): Charge
    {
        $invoice = $this->call('CreateInvoice', [
            'userid' => $customerRef,
            'itemdescription1' => $description,
            'itemamount1' => $amount->amount,
            'itemtaxed1' => false,
            'paymentmethod' => 'bancard',
            'autoapplycredit' => true,
        ]);

        if (($invoice['result'] ?? null) !== 'success') {
            return new Charge(Charge::FAILED, '', $amount, $invoice['message'] ?? 'CreateInvoice falló');
        }

        $status = $this->call('GetInvoice', ['invoiceid' => $invoice['invoiceid']]);
        $paid = ($status['status'] ?? '') === 'Paid';

        return $paid
            ? new Charge(Charge::PAID, (string) $invoice['invoiceid'], $amount)
            : new Charge(Charge::FAILED, (string) $invoice['invoiceid'], $amount, 'La factura quedó pendiente de pago.');
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
