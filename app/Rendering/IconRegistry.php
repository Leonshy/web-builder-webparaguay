<?php

namespace App\Rendering;

/**
 * Registro único y cerrado de íconos (§4 del Anexo A).
 *
 * 46 claves. Si llega una clave fuera del registro, se devuelve el
 * fallback del tipo de sección. No falla, no muestra roto, no inventa.
 *
 * Cada SVG es de trazo (stroke = currentColor), 24x24, sin color literal:
 * hereda el color del contexto vía CSS.
 */
final class IconRegistry
{
    /** Fallback por tipo de sección cuando la clave no existe o falta. */
    private const TYPE_FALLBACK = [
        'hero' => 'bolt',
        'page_header' => 'flag',
        'media_text' => 'check',
        'feature_list' => 'check',
        'cta_banner' => 'send',
        'stats' => 'activity',
        'contact_form' => 'message',
        'rich_text' => 'file',
        'gallery' => 'image',
        'entity_grid' => 'briefcase',
        'testimonials' => 'star',
        'faq' => 'info',
        'pricing_plans' => 'tag',
        'video' => 'video',
    ];

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::paths());
    }

    /**
     * Devuelve el markup SVG de un ícono.
     *
     * @param  string|null  $key  clave pedida (puede ser inválida o null)
     * @param  string|null  $sectionType  para elegir el fallback correcto
     */
    public static function svg(?string $key, ?string $sectionType = null, string $class = 'wp-icon'): string
    {
        $paths = self::paths();

        if ($key === null || ! isset($paths[$key])) {
            $key = self::TYPE_FALLBACK[$sectionType] ?? 'check';
        }

        $inner = $paths[$key] ?? $paths['check'];

        return '<svg class="'.e($class).'" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            .'stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" '
            .'focusable="false">'.$inner.'</svg>';
    }

    /** @return array<string,string> clave => contenido interno del <svg> */
    public static function paths(): array
    {
        return [
            // Comunicación
            'message' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
            'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.4c1 .3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/>',
            'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
            'whatsapp' => '<path d="M3 21l1.7-5A8.4 8.4 0 1 1 8 19.3z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5.6 0 1.2-.4 1.2-1l-.2-1.2-2 -.6-1 1a5 5 0 0 1-2.2-2.2l1-1L9.8 8 8.6 7.8c-.6 0-1 .6-1 1.2z"/>',
            'send' => '<path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/>',
            // Tiempo y proceso
            'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'check' => '<path d="M20 6 9 17l-5-5"/>',
            'refresh' => '<path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/>',
            'flag' => '<path d="M4 22V4s1-1 4-1 5 2 8 2 4-1 4-1v11s-1 1-4 1-5-2-8-2-4 1-4 1"/>',
            // Confianza
            'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>',
            'award' => '<circle cx="12" cy="9" r="6"/><path d="M8.2 13.9 7 22l5-3 5 3-1.2-8.1"/>',
            'star' => '<path d="m12 2 3 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.9 21l1.2-6.8-5-4.9 6.9-1z"/>',
            'certificate' => '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M9 21l3-2 3 2M7 8h10M7 12h6"/>',
            'lock' => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
            // Personas
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
            'heart' => '<path d="M12 21S3 14 3 8.5A4.5 4.5 0 0 1 12 6a4.5 4.5 0 0 1 9 2.5C21 14 12 21 12 21"/>',
            'handshake' => '<path d="m11 17 2 2 4-4 3 3M2 12l4-4 5 5-2 2-3-1zM13 8l3-3 6 6-3 3"/>',
            // Negocio
            'chart' => '<path d="M3 3v18h18"/><path d="M7 15l3-4 4 3 4-6"/>',
            'trending' => '<path d="M3 17 10 10l4 4 7-7"/><path d="M14 7h7v7"/>',
            'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
            'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M2 13h20"/>',
            'tag' => '<path d="M20 12.5 12.5 20a2 2 0 0 1-3 0L3 13.5V4h9.5L20 11a2 2 0 0 1 0 1.5"/><circle cx="7.5" cy="8.5" r="1.5"/>',
            // Industria
            'tool' => '<path d="M14.5 5.5a4 4 0 0 0 5 5L21 12l-8 8a2.8 2.8 0 0 1-4-4l8-8z"/>',
            'gear' => '<circle cx="12" cy="12" r="3.5"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-2.7 1.1V21a2 2 0 0 1-4 0v-.2A1.6 1.6 0 0 0 6.8 19l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.6 1.6 0 0 0 3 12.4H3a2 2 0 0 1 0-4h.2A1.6 1.6 0 0 0 4.6 5.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.6 1.6 0 0 0 12 2.6V3a2 2 0 0 1 4 0v.2a1.6 1.6 0 0 0 2.7 1.1l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0 1.1 2.7H21a2 2 0 0 1 0 4h-.2a1.6 1.6 0 0 0-1.4 1z"/>',
            'truck' => '<path d="M1 4h13v12H1zM14 8h4l3 3v5h-7"/><circle cx="6" cy="19" r="2"/><circle cx="17.5" cy="19" r="2"/>',
            'package' => '<path d="m21 8-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v9"/>',
            'factory' => '<path d="M3 21V9l6 4V9l6 4V4h4v17z"/><path d="M7 21v-4M13 21v-4M17 21v-4"/>',
            // Naturaleza
            'leaf' => '<path d="M4 20s3 .5 7-3.5S20 4 20 4s-2 9-6 12-10 4-10 4"/><path d="M4 20c2-5 6-9 12-11"/>',
            'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
            'drop' => '<path d="M12 22a7 7 0 0 0 7-7c0-5-7-13-7-13S5 10 5 15a7 7 0 0 0 7 7"/>',
            'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18"/>',
            // Ubicación
            'map-pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
            'home' => '<path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2z"/>',
            'building' => '<rect x="4" y="2" width="16" height="20" rx="1"/><path d="M9 22v-4h6v4M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/>',
            // Medios
            'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/>',
            'video' => '<rect x="2" y="6" width="14" height="12" rx="2"/><path d="m22 8-6 4 6 4z"/>',
            'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>',
            'download' => '<path d="M12 3v12"/><path d="m7 12 5 5 5-5"/><path d="M5 21h14"/>',
            'link' => '<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7L12.5 20"/>',
            // Genéricos
            'bolt' => '<path d="M13 2 3 14h7l-1 8 10-12h-7z"/>',
            'eye' => '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7"/><circle cx="12" cy="12" r="3"/>',
            'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
        ];
    }
}
