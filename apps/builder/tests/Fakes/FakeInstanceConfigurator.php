<?php

namespace Tests\Fakes;

use Webparaguay\Provisioning\InstanceConfigurator;

class FakeInstanceConfigurator implements InstanceConfigurator
{
    /** @var array<int,string> */
    public array $configured = [];

    public bool $fail = false;

    public function configure(string $fqdn): array
    {
        if ($this->fail) {
            throw new \Webparaguay\Provisioning\ProvisioningException('Plesk caído (fake)');
        }

        $this->configured[] = $fqdn;

        return ['db' => 'db_'.explode('.', $fqdn)[0], 'db_user' => 'u'];
    }
}
