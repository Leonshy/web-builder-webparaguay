<?php

namespace App\Generation;

use App\Models\Organization;
use App\Models\Project;
use Illuminate\Support\Str;

/**
 * Generador determinístico basado en plantillas.
 *
 * NO usa IA. Sirve como andamio del pipeline (entrevista → generar → sitio →
 * preview) y como fallback si el generador con IA no está configurado.
 * Produce un documento que valida contra site.schema.json.
 *
 * El generador con IA (ClaudeGenerator) implementa la misma interfaz y se
 * elige por config (`BUILDER_GENERATOR`).
 */
final class TemplateGenerator implements Generator
{
    public function generate(Brief $brief, Organization $organization, ?Project $project): array
    {
        $doc = [
            'schema_version' => '0.1',
            'template' => $brief->template === 'institucional' ? 'institucional' : 'landing',
            'theme' => $this->theme($brief),
            'settings' => $brief->settings + ['business_name' => $brief->settings['business_name'] ?? 'Mi empresa'],
            'layout' => [
                'navbar' => ['variant' => 'simple', 'button' => $this->contactButton()],
                'footer' => ['variant' => 'full'],
            ],
            'pages' => $brief->template === 'institucional'
                ? $this->institucionalPages($brief)
                : $this->landingPages($brief),
        ];

        return $doc;
    }

    public function repair(Brief $brief, array $document, array $errors, Organization $organization, ?Project $project): array
    {
        // Determinístico: la única reparación posible es regenerar desde el brief.
        return $this->generate($brief, $organization, $project);
    }

    /** @return array<string,mixed> */
    private function theme(Brief $brief): array
    {
        $colors = $brief->theme['colors'] ?? [];

        return [
            'colors' => [
                'primary' => $colors['primary'] ?? '#1f6f5c',
                'accent' => $colors['accent'] ?? '#e0a24b',
                'background' => $colors['background'] ?? '#fbfaf7',
                'text' => $colors['text'] ?? '#1a1a1a',
            ],
            'typography' => ['pairing' => $brief->theme['typography']['pairing'] ?? 'manrope'],
            'shape' => $brief->theme['shape'] ?? ['radius' => 'md', 'shadow' => 'soft', 'density' => 'normal'],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function landingPages(Brief $brief): array
    {
        $sections = [$this->hero($brief, 0)];
        $order = 1;

        if ($brief->services !== []) {
            $sections[] = $this->featureList($brief, $order++, 'grid');
        }
        if ($brief->aboutText) {
            $sections[] = $this->mediaText($brief, $order++);
        }
        $sections[] = $this->cta($order++, 'single');
        $sections[] = $this->contactForm($order++, 'form_info');

        return [[
            'slug' => 'inicio', 'title' => 'Inicio', 'is_home' => true, 'order' => 0,
            'seo' => $this->seo($brief, $brief->settings['business_name'] ?? 'Inicio'),
            'sections' => $sections,
        ]];
    }

    /** @return array<int,array<string,mixed>> */
    private function institucionalPages(Brief $brief): array
    {
        $home = [$this->hero($brief, 0)];
        $o = 1;
        if ($brief->services !== []) {
            $home[] = $this->featureList($brief, $o++, 'cards');
        }
        $home[] = $this->cta($o++, 'dual');
        $home[] = $this->contactForm($o++, 'form_map');

        $pages = [[
            'slug' => 'inicio', 'title' => 'Inicio', 'is_home' => true, 'order' => 0,
            'seo' => $this->seo($brief, $brief->settings['business_name'] ?? 'Inicio'),
            'sections' => $home,
        ]];

        if ($brief->aboutText) {
            $pages[] = [
                'slug' => 'nosotros', 'title' => 'Nosotros', 'order' => 1,
                'sections' => [
                    $this->pageHeader('Nosotros', 0),
                    ['id' => 'rt_about', 'type' => 'rich_text', 'variant' => 'default', 'order' => 1,
                        'content' => ['body' => $this->richtext($brief->aboutText), 'width' => 'normal']],
                ],
            ];
        }

        if ($brief->services !== []) {
            $pages[] = [
                'slug' => 'servicios', 'title' => 'Servicios', 'order' => 2,
                'sections' => [
                    $this->pageHeader('Servicios', 0),
                    $this->entityGrid($brief, 1),
                ],
            ];
        }

        $pages[] = [
            'slug' => 'contacto', 'title' => 'Contacto', 'order' => 3,
            'sections' => [
                $this->pageHeader('Contacto', 0),
                $this->contactForm(1, 'form_map'),
            ],
        ];

        return $pages;
    }

    /** @return array<string,mixed> */
    private function hero(Brief $brief, int $order): array
    {
        $name = $brief->settings['business_name'] ?? 'Mi empresa';

        return [
            'id' => 'hero', 'type' => 'hero', 'variant' => 'split', 'order' => $order, 'anchor' => 'inicio',
            'content' => array_filter([
                'headline' => Str::limit($brief->settings['tagline'] ?? $name, 68, ''),
                'subheadline' => $brief->settings['industry'] ?? null,
                'primary_button' => $this->contactButton(),
                'secondary_button' => ! empty($brief->settings['whatsapp'])
                    ? ['label' => 'Escribir por WhatsApp', 'action' => 'whatsapp', 'style' => 'whatsapp', 'icon' => 'whatsapp']
                    : null,
            ], fn ($v) => $v !== null),
        ];
    }

    /** @return array<string,mixed> */
    private function featureList(Brief $brief, int $order, string $variant): array
    {
        return [
            'id' => 'features_'.$order, 'type' => 'feature_list', 'variant' => $variant, 'order' => $order,
            'label' => 'Qué hacemos', 'title' => 'Nuestros servicios',
            'content' => [
                'items' => array_map(fn ($s) => array_filter([
                    'icon' => $s['icon'] ?? 'gear',
                    'title' => Str::limit($s['name'], 58, ''),
                    'description' => isset($s['description']) ? Str::limit($s['description'], 210, '') : null,
                ], fn ($v) => $v !== null), array_slice($brief->services, 0, 12)),
                'columns' => 3,
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function entityGrid(Brief $brief, int $order): array
    {
        return [
            'id' => 'entities_'.$order, 'type' => 'entity_grid', 'variant' => 'card_full', 'order' => $order,
            'title' => 'Servicios',
            'content' => [
                'source' => 'manual',
                'items' => array_map(fn ($s) => array_filter([
                    'title' => Str::limit($s['name'], 58, ''),
                    'description' => isset($s['description']) ? Str::limit($s['description'], 210, '') : null,
                ], fn ($v) => $v !== null), array_slice($brief->services, 0, 12)),
                'columns' => 3,
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function mediaText(Brief $brief, int $order): array
    {
        return [
            'id' => 'about_'.$order, 'type' => 'media_text', 'variant' => 'no_image', 'order' => $order,
            'label' => 'Quiénes somos', 'title' => 'Sobre nosotros',
            'content' => ['body' => $this->richtext($brief->aboutText ?? '')],
        ];
    }

    /** @return array<string,mixed> */
    private function cta(int $order, string $variant): array
    {
        return [
            'id' => 'cta_'.$order, 'type' => 'cta_banner', 'variant' => $variant, 'order' => $order,
            'background' => 'dark', 'title' => '¿Hablamos?',
            'content' => array_filter([
                'body' => 'Contanos qué necesitás y te respondemos a la brevedad.',
                'primary_button' => $this->contactButton(),
                'secondary_button' => $variant === 'dual'
                    ? ['label' => 'WhatsApp', 'action' => 'whatsapp', 'style' => 'whatsapp', 'icon' => 'whatsapp']
                    : null,
            ], fn ($v) => $v !== null),
        ];
    }

    /** @return array<string,mixed> */
    private function contactForm(int $order, string $variant): array
    {
        return [
            'id' => 'contact_'.$order, 'type' => 'contact_form', 'variant' => $variant, 'order' => $order,
            'anchor' => 'contacto', 'label' => 'Contacto', 'title' => 'Contactanos',
            'content' => [
                'fields' => ['name', 'email', 'phone', 'message'],
                'submit_label' => 'Enviar consulta',
                'show_map' => $variant === 'form_map',
                'show_socials' => true,
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function pageHeader(string $heading, int $order): array
    {
        return [
            'id' => 'ph_'.Str::slug($heading), 'type' => 'page_header', 'variant' => 'simple', 'order' => $order,
            'content' => ['heading' => $heading],
        ];
    }

    /** @return array<string,mixed> */
    private function contactButton(): array
    {
        return ['label' => 'Contactar', 'action' => 'anchor', 'anchor' => 'contacto'];
    }

    /** @return array<string,mixed> */
    private function seo(Brief $brief, string $title): array
    {
        return array_filter([
            'title' => Str::limit($title, 58, ''),
            'description' => isset($brief->settings['tagline']) ? Str::limit($brief->settings['tagline'], 158, '') : null,
        ], fn ($v) => $v !== null);
    }

    private function richtext(string $text): string
    {
        $text = trim(strip_tags($text));
        if ($text === '') {
            return '<p>Contenido pendiente.</p>';
        }

        return collect(preg_split('/\n{2,}/', $text))
            ->map(fn ($p) => '<p>'.e(trim($p)).'</p>')
            ->implode('');
    }
}
