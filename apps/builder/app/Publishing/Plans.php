<?php

namespace App\Publishing;

use Webparaguay\Provisioning\HostingPlan;
use Webparaguay\Provisioning\Money;

/**
 * Planes de publicación. "Publicar = comprar": el botón de publicar es la caja.
 *
 * En el MVP hay un solo plan, que corresponde 1:1 al producto de WHMCS
 * (`WHMCS_PRODUCT_ID`). El precio real y la facturación los lleva WHMCS;
 * `PUBLISHING_PLAN_PRICE` es sólo para mostrar y para el registro de pago.
 */
final class Plans
{
    public const DEFAULT = 'web';

    /** @return array<string,HostingPlan> */
    public static function all(): array
    {
        return [
            'web' => new HostingPlan(
                code: (string) config('publishing.whmcs_product_id', 'web'),
                label: 'Sitio web',
                price: new Money((int) config('publishing.plan_price', 39900)),
                billingMonths: 1,
            ),
        ];
    }

    public static function get(string $code): HostingPlan
    {
        return self::all()[$code] ?? throw new \InvalidArgumentException("Plan desconocido: {$code}");
    }
}
