<?php

namespace App\Generation;

/**
 * Propone 2-3 paletas cuando el cliente llega sin logo, sin colores y sin idea
 * de tipografía. La etapa 1 no puede trabar (UC-02).
 *
 * Determinístico: elige un set por rubro y rota con cada "mostrame otras".
 * Cada paleta trae los 4 colores obligatorios + una tipografía de la lista
 * curada. El resto de los tonos los deriva ThemeHelper en el renderer.
 */
final class PaletteProposer
{
    /** @var array<string,array<int,array{name:string,colors:array<string,string>,pairing:string}>> */
    private const BY_INDUSTRY = [
        'industria' => [
            ['name' => 'Industrial sobrio', 'colors' => ['primary' => '#1f2d3d', 'accent' => '#c9a227', 'background' => '#f4f4f2', 'text' => '#1a1a1a'], 'pairing' => 'space-grotesk-ibm-plex'],
            ['name' => 'Verde confianza', 'colors' => ['primary' => '#1f6f5c', 'accent' => '#e0a24b', 'background' => '#fbfaf7', 'text' => '#1a1a1a'], 'pairing' => 'playfair-inter'],
            ['name' => 'Azul técnico', 'colors' => ['primary' => '#20507a', 'accent' => '#e2683c', 'background' => '#ffffff', 'text' => '#141a20'], 'pairing' => 'sora-inter'],
        ],
        'salud' => [
            ['name' => 'Cuidado sereno', 'colors' => ['primary' => '#2f7d8c', 'accent' => '#6fbf9b', 'background' => '#fbfdfd', 'text' => '#182226'], 'pairing' => 'libre-franklin-lora'],
            ['name' => 'Cálido cercano', 'colors' => ['primary' => '#3a6b8a', 'accent' => '#e8956b', 'background' => '#fdfaf6', 'text' => '#20262b'], 'pairing' => 'fraunces-inter'],
            ['name' => 'Clínico claro', 'colors' => ['primary' => '#2457a6', 'accent' => '#41b0a0', 'background' => '#ffffff', 'text' => '#151b26'], 'pairing' => 'manrope'],
        ],
        'gastronomia' => [
            ['name' => 'Apetito cálido', 'colors' => ['primary' => '#8a3b28', 'accent' => '#e0a24b', 'background' => '#fdf7f0', 'text' => '#211610'], 'pairing' => 'dm-serif-dm-sans'],
            ['name' => 'Fresco de mercado', 'colors' => ['primary' => '#3f7d4f', 'accent' => '#e2683c', 'background' => '#fbfaf5', 'text' => '#1a2016'], 'pairing' => 'fraunces-inter'],
            ['name' => 'Nocturno elegante', 'colors' => ['primary' => '#6b2440', 'accent' => '#c9a227', 'background' => '#faf6f4', 'text' => '#1c1216'], 'pairing' => 'playfair-inter'],
        ],
        'servicios' => [
            ['name' => 'Profesional claro', 'colors' => ['primary' => '#2a4d8f', 'accent' => '#f2994a', 'background' => '#fbfbfd', 'text' => '#161a22'], 'pairing' => 'manrope'],
            ['name' => 'Confianza verde', 'colors' => ['primary' => '#1f6f5c', 'accent' => '#3a6b8a', 'background' => '#fbfaf7', 'text' => '#1a1a1a'], 'pairing' => 'sora-inter'],
            ['name' => 'Editorial serio', 'colors' => ['primary' => '#2c2c2c', 'accent' => '#b5502a', 'background' => '#f7f6f3', 'text' => '#171717'], 'pairing' => 'libre-franklin-lora'],
        ],
    ];

    private const FALLBACK = 'servicios';

    /**
     * @return array<int,array{name:string,colors:array<string,string>,pairing:string}>
     */
    public function propose(?string $industry, int $rotation = 0): array
    {
        $key = self::FALLBACK;
        $needle = mb_strtolower((string) $industry);
        foreach (array_keys(self::BY_INDUSTRY) as $candidate) {
            if ($needle !== '' && str_contains($needle, $candidate)) {
                $key = $candidate;
                break;
            }
        }
        // Heurística de rubros comunes que no coinciden por nombre exacto.
        $key = match (true) {
            str_contains($needle, 'metal'), str_contains($needle, 'fabric'), str_contains($needle, 'taller') => 'industria',
            str_contains($needle, 'restau'), str_contains($needle, 'comida'), str_contains($needle, 'caf') => 'gastronomia',
            str_contains($needle, 'clinic'), str_contains($needle, 'consult'), str_contains($needle, 'odont') => 'salud',
            default => $key,
        };

        $sets = self::BY_INDUSTRY[$key];

        // Rotar el orden con cada "mostrame otras".
        $offset = $rotation % count($sets);

        return array_merge(array_slice($sets, $offset), array_slice($sets, 0, $offset));
    }
}
