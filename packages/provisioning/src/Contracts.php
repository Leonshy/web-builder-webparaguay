<?php

namespace Webparaguay\Provisioning;

/**
 * Cobro y alta de cliente. Hoy: WHMCS. Mañana: plataforma propia.
 * El builder llama a esta interfaz, nunca a WHMCS.
 */
interface BillingGateway
{
    /** Devuelve el id de cliente en la pasarela (lo crea si no existe). */
    public function ensureCustomer(string $name, string $email): string;

    public function charge(string $customerRef, Money $amount, string $description): Charge;
}

/**
 * Alta de hosting y despliegue del paquete versionado del CMS. Hoy: Plesk.
 *
 * El despliegue es por git: el servidor hace `git pull` del tag de
 * site-runtime. Nunca se sube código a mano.
 */
interface HostingProvisioner
{
    public function createAccount(string $customerRef, HostingPlan $plan, string $subdomainLabel): HostingAccount;

    /** Apunta la cuenta al tag de site-runtime y dispara el pull. */
    public function deploySiteRuntime(string $accountRef, string $siteRef, string $version): void;

    /** Agrega un dominio (alias) a la cuenta, con DNS y SSL. */
    public function attachDomain(string $accountRef, string $fqdn): void;
}

/**
 * Registro de dominios. gTLD (.com, .net) por WHMCS: automático.
 * `.com.py` por NIC.py: NO tiene API, es manual (formulario + transferencia).
 */
interface DomainRegistrar
{
    /**
     * @return DomainOutcome  para gTLD queda `gtld_live`; para .com.py queda
     *                        `compy_pending` y el sitio sigue vivo en el subdominio.
     */
    public function provision(string $customerRef, DomainRequest $request, string $subdomainFqdn): DomainOutcome;
}
