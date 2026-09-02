<?php

namespace Webparaguay\Provisioning;

/**
 * Publica un sitio: cobra/ordena, aprovisiona el hosting, deja el CMS
 * desplegado y el dominio configurado.
 *
 * Hay dos implementaciones:
 *  - Provisioner        — compone BillingGateway + HostingProvisioner +
 *                         DomainRegistrar (fakes en dev, o clientes directos).
 *  - Whmcs\WhmcsProvisioner — flujo nativo de WHMCS: coloca una orden de un
 *                         producto ya configurado y WHMCS provisiona en Plesk.
 */
interface SitePublisher
{
    public function publish(PublishInput $input): PublishResult;
}
