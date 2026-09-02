<?php

namespace App\Generation;

use App\Models\InterviewDraft;

/**
 * El brief: lo relevado por la entrevista, normalizado.
 *
 * NO es el JSON del sitio. Es el insumo con el que un generador arma
 * `pages[]` y `sections[]`. La salida del generador se valida siempre
 * contra site.schema.json.
 */
final class Brief
{
    /**
     * @param  array<string,mixed>  $theme    theme.* (marca)
     * @param  array<string,mixed>  $settings settings.* (contacto)
     * @param  array<int,array{name:string,description?:string,icon?:string}>  $services
     * @param  array<string,mixed>  $assumptions  campos asumidos por default, para mostrar en el resumen
     */
    public function __construct(
        public string $template,
        public string $goal,
        public array $theme,
        public array $settings,
        public array $services = [],
        public ?string $aboutText = null,
        public array $referenceTexts = [],
        public array $assumptions = [],
    ) {}

    public static function fromDraft(InterviewDraft $draft): self
    {
        $brand = $draft->brand ?? [];
        $purpose = $draft->purpose ?? [];
        $content = $draft->content ?? [];

        $theme = [
            'colors' => array_filter([
                'primary' => $brand['colors']['primary'] ?? null,
                'accent' => $brand['colors']['accent'] ?? null,
                'background' => $brand['colors']['background'] ?? null,
                'text' => $brand['colors']['text'] ?? null,
            ]),
            'typography' => ['pairing' => $brand['typography']['pairing'] ?? 'manrope'],
        ];
        if (! empty($brand['shape'])) {
            $theme['shape'] = $brand['shape'];
        }

        $settings = array_filter([
            'business_name' => $content['business_name'] ?? ($purpose['business_name'] ?? 'Mi empresa'),
            'tagline' => $content['tagline'] ?? null,
            'industry' => $purpose['industry'] ?? null,
            'email' => $content['email'] ?? null,
            'phone' => $content['phone'] ?? null,
            'whatsapp' => $content['whatsapp'] ?? null,
            'address' => $content['address'] ?? null,
            'schedule' => $content['schedule'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if (! empty($content['socials'])) {
            $settings['socials'] = $content['socials'];
        }

        return new self(
            template: $purpose['template'] ?? 'landing',
            goal: $purpose['goal'] ?? 'contact',
            theme: $theme,
            settings: $settings,
            services: $content['services'] ?? [],
            aboutText: $content['about_text'] ?? null,
            referenceTexts: $content['reference_texts'] ?? [],
            assumptions: array_merge($brand['assumptions'] ?? [], $purpose['assumptions'] ?? [], $content['assumptions'] ?? []),
        );
    }
}
