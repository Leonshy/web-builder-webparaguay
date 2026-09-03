<?php

namespace Tests\Feature;

use Webparaguay\Schema\SchemaValidator;
use Tests\TestCase;

/**
 * §9 del Anexo A: el catálogo está completo cuando las dos plantillas
 * originales se reconstruyen eligiendo tipos y variantes.
 *
 * `variants-gallery.json` ejercita las 41 variantes; `example-site.json`
 * es la reconstrucción de la plantilla institucional (IGP Metales).
 */
class VariantsGalleryTest extends TestCase
{
    public function test_el_fixture_de_variantes_valida_contra_el_esquema(): void
    {
        $raw = json_decode((string) file_get_contents(config('site-runtime.variants_site_path')), true);

        $this->assertSame([], (new SchemaValidator())->errors($raw));
    }

    public static function paginasDeVariantes(): array
    {
        return array_map(fn ($s) => [$s], [
            'hero', 'page-header', 'media-text', 'feature-list', 'cta-banner',
            'contact-form', 'stats', 'rich-text', 'gallery', 'entity-grid',
            'testimonials', 'faq', 'pricing-plans', 'video',
        ]);
    }

    /** @dataProvider paginasDeVariantes */
    public function test_cada_pagina_de_variantes_renderiza_sin_secciones_sin_implementar(string $slug): void
    {
        $html = $this->get("/variants/{$slug}")->assertOk()->getContent();

        $this->assertStringNotContainsString('no implementada', $html, "La página {$slug} tiene variantes sin plantilla.");
        $this->assertStringNotContainsString('(sin type)', $html);
    }

    public function test_la_plantilla_institucional_se_reconstruye_entera(): void
    {
        // example-site.json usa los 14 tipos. Ninguna sección debe caer al placeholder.
        $home = $this->get('/preview')->assertOk()->getContent();
        $nosotros = $this->get('/preview/nosotros')->assertOk()->getContent();

        foreach ([$home, $nosotros] as $html) {
            $this->assertStringNotContainsString('no implementada', $html);
        }

        // Secciones que en la Tarea 1 caían al placeholder y ahora renderizan.
        $this->assertStringContainsString('Nuestros servicios', $home);          // entity_grid card_full
        $this->assertStringContainsString('youtube-nocookie.com/embed/', $home);  // video embed
        $this->assertStringContainsString('Nos resolvieron un repuesto', $home);  // testimonials slider_dark
        $this->assertStringContainsString('Contrato mensual', $home);             // pricing_plans cards
        $this->assertStringContainsString('wp-faq__item', $home);                 // faq accordion_two_col
        $this->assertStringContainsString('name="message"', $home);               // contact_form form_map
        $this->assertStringContainsString('Nuestra historia', $nosotros);         // rich_text default
    }

    public function test_el_formulario_de_contacto_es_un_stub_que_acusa_recibo(): void
    {
        $this->post('/contacto', ['name' => 'x', 'email' => 'x@y.com', 'consent' => '1'])
            ->assertRedirect();

        $html = $this->get('/preview')->getContent();
        $this->assertStringContainsString('recibimos tu mensaje', strtolower($html));
    }
}
