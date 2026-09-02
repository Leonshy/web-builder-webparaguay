<?php

namespace Webparaguay\Provisioning;

/** Monto entero en la unidad mínima (guaraníes: sin decimales). */
final class Money
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency = 'PYG',
    ) {}

    public function format(): string
    {
        return $this->currency.' '.number_format($this->amount, 0, ',', '.');
    }
}

final class HostingPlan
{
    public function __construct(
        public readonly string $code,        // whmcs product id / plesk plan
        public readonly string $label,
        public readonly Money $price,
        public readonly int $billingMonths = 1,
    ) {}
}

/** Qué dominio quiere el cliente. */
final class DomainRequest
{
    public const SUBDOMAIN = 'subdomain';
    public const GTLD = 'gtld';        // .com, .net, ...
    public const COMPY = 'compy';      // .com.py — NIC.py no tiene API

    public function __construct(
        public readonly string $kind,
        public readonly string $value,   // "talleresyvytu" o "talleresyvytu.com" o "talleresyvytu.com.py"
    ) {}
}

/** Resultado de un cobro. */
final class Charge
{
    public const PAID = 'paid';
    public const FAILED = 'failed';

    public function __construct(
        public readonly string $status,
        public readonly string $gatewayRef,
        public readonly Money $amount,
        public readonly ?string $failureReason = null,
    ) {}

    public function paid(): bool
    {
        return $this->status === self::PAID;
    }
}

/** Estado del dominio, que es un estado del proyecto, no un error. */
final class DomainOutcome
{
    public const SUBDOMAIN_LIVE = 'subdomain_live';
    public const GTLD_LIVE = 'gtld_live';
    public const COMPY_PENDING = 'compy_pending';   // sitio vivo en subdominio, .com.py en trámite

    public function __construct(
        public readonly string $status,
        public readonly string $liveFqdn,            // el dominio que ya sirve el sitio ahora
        public readonly ?string $pendingFqdn = null, // el que está en trámite, si hay
        public readonly ?string $backofficeNote = null,
    ) {}
}

/** Cuenta de hosting aprovisionada. */
final class HostingAccount
{
    public function __construct(
        public readonly string $accountRef,
        public readonly string $subdomainFqdn,
    ) {}
}

/** Resultado de una publicación completa. */
final class PublishResult
{
    public function __construct(
        public readonly Charge $charge,
        public readonly HostingAccount $account,
        public readonly DomainOutcome $domain,
        public readonly string $runtimeVersion,
    ) {}
}

/** Entrada de una publicación. */
final class PublishInput
{
    public function __construct(
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $siteRef,          // id del sitio en site-runtime
        public readonly string $siteName,
        public readonly HostingPlan $plan,
        public readonly DomainRequest $domain,
        public readonly string $subdomainLabel,   // etiqueta para <label>.webparaguay.com
    ) {}
}
