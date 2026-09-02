<?php

namespace Webparaguay\Provisioning\Fake;

use Webparaguay\Provisioning\BillingGateway;
use Webparaguay\Provisioning\Charge;
use Webparaguay\Provisioning\Money;

/** Simula WHMCS. Default en dev y en tests. */
final class FakeBillingGateway implements BillingGateway
{
    /** @var array<int,array{customerRef:string,amount:Money,description:string}> */
    public array $charges = [];

    public function __construct(public bool $declineNext = false) {}

    public function ensureCustomer(string $name, string $email): string
    {
        return 'cust_'.substr(md5($email), 0, 10);
    }

    public function charge(string $customerRef, Money $amount, string $description): Charge
    {
        if ($this->declineNext) {
            $this->declineNext = false;

            return new Charge(Charge::FAILED, '', $amount, 'tarjeta rechazada (simulado)');
        }

        $this->charges[] = compact('customerRef', 'amount', 'description');

        return new Charge(Charge::PAID, 'inv_'.count($this->charges), $amount);
    }
}
