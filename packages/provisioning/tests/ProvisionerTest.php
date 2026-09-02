<?php

namespace Webparaguay\Provisioning\Tests;

use PHPUnit\Framework\TestCase;
use Webparaguay\Provisioning\DomainOutcome;
use Webparaguay\Provisioning\DomainRequest;
use Webparaguay\Provisioning\Fake\FakeBillingGateway;
use Webparaguay\Provisioning\Fake\FakeDomainRegistrar;
use Webparaguay\Provisioning\Fake\FakeHostingProvisioner;
use Webparaguay\Provisioning\HostingPlan;
use Webparaguay\Provisioning\Money;
use Webparaguay\Provisioning\ProvisioningException;
use Webparaguay\Provisioning\Provisioner;
use Webparaguay\Provisioning\PublishInput;

class ProvisionerTest extends TestCase
{
    private function input(string $domainKind = DomainRequest::SUBDOMAIN, string $domainValue = 'talleresyvytu'): PublishInput
    {
        return new PublishInput(
            customerName: 'Juan Metal',
            customerEmail: 'juan@ejemplo.com.py',
            siteRef: '42',
            siteName: 'Talleres Yvytu',
            plan: new HostingPlan('basic', 'Básico', new Money(150_000), 1),
            domain: new DomainRequest($domainKind, $domainValue),
            subdomainLabel: 'talleresyvytu',
        );
    }

    private function provisioner(FakeBillingGateway $billing = null, FakeHostingProvisioner $hosting = null): Provisioner
    {
        return new Provisioner(
            $billing ?? new FakeBillingGateway(),
            $hosting ?? new FakeHostingProvisioner(),
            new FakeDomainRegistrar(),
            runtimeVersion: '1.4.2',
        );
    }

    public function test_publicacion_con_subdominio_cobra_aprovisiona_y_deja_el_sitio_vivo(): void
    {
        $billing = new FakeBillingGateway();
        $hosting = new FakeHostingProvisioner();

        $result = $this->provisioner($billing, $hosting)->publish($this->input());

        $this->assertTrue($result->charge->paid());
        $this->assertCount(1, $billing->charges);
        $this->assertSame(DomainOutcome::SUBDOMAIN_LIVE, $result->domain->status);
        $this->assertSame('talleresyvytu.webparaguay.com', $result->domain->liveFqdn);

        // Se desplegó el paquete versionado, apuntando al sitio correcto.
        $this->assertCount(1, $hosting->deploys);
        $this->assertSame('1.4.2', $hosting->deploys[0]['version']);
        $this->assertSame('42', $hosting->deploys[0]['siteRef']);
    }

    public function test_si_el_cobro_no_pasa_no_se_aprovisiona_nada(): void
    {
        $hosting = new FakeHostingProvisioner();
        $billing = new FakeBillingGateway(declineNext: true);

        $this->expectException(ProvisioningException::class);

        try {
            $this->provisioner($billing, $hosting)->publish($this->input());
        } finally {
            $this->assertCount(0, $hosting->deploys);
            $this->assertCount(0, $hosting->domains);
        }
    }

    public function test_com_py_deja_el_sitio_vivo_en_subdominio_y_el_dominio_en_tramite(): void
    {
        $result = $this->provisioner()->publish($this->input(DomainRequest::COMPY, 'talleresyvytu.com.py'));

        $this->assertSame(DomainOutcome::COMPY_PENDING, $result->domain->status);
        $this->assertSame('talleresyvytu.webparaguay.com', $result->domain->liveFqdn); // vivo ya
        $this->assertSame('talleresyvytu.com.py', $result->domain->pendingFqdn);
        $this->assertNotNull($result->domain->backofficeNote);
    }

    public function test_gtld_queda_vivo_de_inmediato(): void
    {
        $hosting = new FakeHostingProvisioner();
        $result = $this->provisioner(hosting: $hosting)->publish($this->input(DomainRequest::GTLD, 'talleresyvytu.com'));

        $this->assertSame(DomainOutcome::GTLD_LIVE, $result->domain->status);
        $this->assertContains('talleresyvytu.com', $hosting->domains);
    }
}
