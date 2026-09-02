<?php

namespace Webparaguay\Provisioning\Fake;

use Webparaguay\Provisioning\HostingAccount;
use Webparaguay\Provisioning\HostingPlan;
use Webparaguay\Provisioning\HostingProvisioner;

/** Simula Plesk + el deploy por git. */
final class FakeHostingProvisioner implements HostingProvisioner
{
    /** @var array<int,array<string,string>> */
    public array $deploys = [];

    /** @var array<int,string> */
    public array $domains = [];

    public function __construct(private string $subdomainBase = 'webparaguay.com') {}

    public function createAccount(string $customerRef, HostingPlan $plan, string $subdomainLabel): HostingAccount
    {
        return new HostingAccount('acct_'.$subdomainLabel, "{$subdomainLabel}.{$this->subdomainBase}");
    }

    public function deploySiteRuntime(string $accountRef, string $siteRef, string $version): void
    {
        $this->deploys[] = compact('accountRef', 'siteRef', 'version');
    }

    public function attachDomain(string $accountRef, string $fqdn): void
    {
        $this->domains[] = $fqdn;
    }
}
