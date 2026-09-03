<?php

namespace App\Rendering\Theme;

/**
 * Lista curada de combinaciones tipográficas (§5.2 del Anexo A).
 *
 * El agente elige UNA clave de esta lista, no una fuente libre.
 * Todas las opciones funcionan; elegir tipografías es difícil y la
 * mayoría de las combinaciones son malas.
 *
 * Cada entrada define las familias, el peso sugerido de títulos y la
 * URL de Google Fonts. Las familias nunca se escriben en un componente:
 * salen de las variables CSS --wp-font-heading / --wp-font-body.
 */
final class FontPairings
{
    /**
     * @return array<string, array{label:string, heading:string, body:string, heading_stack:string, body_stack:string, google:string}>
     */
    public static function all(): array
    {
        $sans = 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif';
        $serif = 'ui-serif, Georgia, Cambria, "Times New Roman", serif';

        return [
            'playfair-inter' => [
                'label' => 'Playfair Display + Inter',
                'heading' => 'Playfair Display', 'body' => 'Inter',
                'heading_stack' => "\"Playfair Display\", {$serif}",
                'body_stack' => "\"Inter\", {$sans}",
                'google' => 'family=Playfair+Display:wght@500;600;700;800&family=Inter:wght@300;400;500;600;700',
            ],
            'fraunces-inter' => [
                'label' => 'Fraunces + Inter',
                'heading' => 'Fraunces', 'body' => 'Inter',
                'heading_stack' => "\"Fraunces\", {$serif}",
                'body_stack' => "\"Inter\", {$sans}",
                'google' => 'family=Fraunces:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700',
            ],
            'sora-inter' => [
                'label' => 'Sora + Inter',
                'heading' => 'Sora', 'body' => 'Inter',
                'heading_stack' => "\"Sora\", {$sans}",
                'body_stack' => "\"Inter\", {$sans}",
                'google' => 'family=Sora:wght@500;600;700;800&family=Inter:wght@300;400;500;600',
            ],
            'space-grotesk-ibm-plex' => [
                'label' => 'Space Grotesk + IBM Plex Sans',
                'heading' => 'Space Grotesk', 'body' => 'IBM Plex Sans',
                'heading_stack' => "\"Space Grotesk\", {$sans}",
                'body_stack' => "\"IBM Plex Sans\", {$sans}",
                'google' => 'family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@300;400;500;600',
            ],
            'dm-serif-dm-sans' => [
                'label' => 'DM Serif Display + DM Sans',
                'heading' => 'DM Serif Display', 'body' => 'DM Sans',
                'heading_stack' => "\"DM Serif Display\", {$serif}",
                'body_stack' => "\"DM Sans\", {$sans}",
                'google' => 'family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700',
            ],
            'poppins-work-sans' => [
                'label' => 'Poppins + Work Sans',
                'heading' => 'Poppins', 'body' => 'Work Sans',
                'heading_stack' => "\"Poppins\", {$sans}",
                'body_stack' => "\"Work Sans\", {$sans}",
                'google' => 'family=Poppins:wght@500;600;700;800&family=Work+Sans:wght@300;400;500;600',
            ],
            'libre-franklin-lora' => [
                'label' => 'Libre Franklin + Lora',
                'heading' => 'Libre Franklin', 'body' => 'Lora',
                'heading_stack' => "\"Libre Franklin\", {$sans}",
                'body_stack' => "\"Lora\", {$serif}",
                'google' => 'family=Libre+Franklin:wght@500;600;700;800&family=Lora:wght@400;500;600',
            ],
            'manrope' => [
                'label' => 'Manrope',
                'heading' => 'Manrope', 'body' => 'Manrope',
                'heading_stack' => "\"Manrope\", {$sans}",
                'body_stack' => "\"Manrope\", {$sans}",
                'google' => 'family=Manrope:wght@400;500;600;700;800',
            ],
        ];
    }

    public const FALLBACK = 'manrope';

    /**
     * @return array{label:string, heading:string, body:string, heading_stack:string, body_stack:string, google:string}
     */
    public static function resolve(?string $key): array
    {
        $all = self::all();

        return $all[$key] ?? $all[self::FALLBACK];
    }
}
