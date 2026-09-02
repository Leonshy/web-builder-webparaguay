<?php

namespace App\Publishing;

use Webparaguay\Provisioning\HostingPlan;
use Webparaguay\Provisioning\Money;

/**
 * Planes de publicación. "Publicar = comprar": el botón de publicar es la caja.
 * Los precios son de referencia del MVP; la fuente real será WHMCS.
 */
final class Plans
{
    /** @return array<string,HostingPlan> */
    public static function all(): array
    {
        return [
            'basico' => new HostingPlan('wp-basico', 'Básico', new Money(120_000), 1),
            'profesional' => new HostingPlan('wp-pro', 'Profesional', new Money(220_000), 1),
        ];
    }

    public static function get(string $code): HostingPlan
    {
        return self::all()[$code] ?? throw new \InvalidArgumentException("Plan desconocido: {$code}");
    }
}
