<?php

namespace Tests\Feature;

use App\Rendering\SchemaValidator;
use Tests\TestCase;

/**
 * El renderer valida el contrato completo sin depender de IA ni de BD.
 * Estas pruebas comprueban que el contenido de cada sección implementada
 * está en el HTML servido SIN ejecutar JavaScript (el SEO es la razón por
 * la que el cliente compra).
 */
class PreviewRenderTest extends TestCase
{
    public function test_el_sitio_de_ejemplo_valida_contra_el_esquema(): void
    {
        $raw = json_decode((string) file_get_contents(config('site-runtime.preview_site_path')), true);

        $this->assertSame([], (new SchemaValidator())->errors($raw));
    }

    public function test_la_home_renderiza_las_secciones_implementadas_en_el_html(): void
    {
        $html = $this->get('/preview')->assertOk()->getContent();

        // hero/split
        $this->assertStringContainsString('Piezas metalicas que aguantan el trabajo pesado', $html);
        $this->assertStringContainsString('https://wa.me/595981000000', $html);
        // feature_list/bar
        $this->assertStringContainsString('Reciclamos el 90% de nuestra chatarra', $html);
        // media_text/image_right + richtext sanitizado
        $this->assertStringContainsString('Veinte anios haciendo piezas para la industria', $html);
        $this->assertStringContainsString('<p>Empezamos como un taller de barrio', $html);
        // stats/row: value numérico + suffix separado, presente sin JS
        $this->assertStringContainsString('data-value="350"', $html);
        $this->assertStringContainsString('>350+<', $html);
        // feature_list/timeline
        $this->assertStringContainsString('Del plano a la pieza en cuatro pasos', $html);
        // cta_banner/dual
        $this->assertStringContainsString('Tenés un plano listo?', $html);
    }

    public function test_la_navegacion_se_deriva_de_las_paginas_y_no_se_escribe_a_mano(): void
    {
        $html = $this->get('/preview')->assertOk()->getContent();

        $this->assertStringContainsString('>Inicio</a>', $html);
        $this->assertStringContainsString('>Nosotros</a>', $html);
        $this->assertStringContainsString('href="/preview/nosotros"', $html);
    }

    public function test_la_pagina_interior_genera_breadcrumb_desde_la_jerarquia(): void
    {
        $html = $this->get('/preview/nosotros')->assertOk()->getContent();

        $this->assertStringContainsString('wp-breadcrumb', $html);
        $this->assertStringContainsString('<a href="/preview">Inicio</a>', $html);
        $this->assertStringContainsString('Mision, vision y valores', $html);
    }

    public function test_el_theme_se_materializa_como_variables_css_en_root(): void
    {
        $html = $this->get('/preview')->assertOk()->getContent();

        $this->assertStringContainsString('--wp-primary: #1f6f5c', strtolower($html));
        $this->assertStringContainsString('--wp-primary-dark:', strtolower($html));
        $this->assertStringContainsString('fonts.googleapis.com/css2', $html);
        // Ningún color de marca literal fuera del bloque :root del ThemeHelper.
        $body = substr($html, strpos($html, '</head>'));
        $this->assertStringNotContainsStringIgnoringCase('#1F6F5C', $body);
    }

    public function test_un_slug_inexistente_devuelve_404(): void
    {
        $this->get('/preview/no-existe')->assertNotFound();
    }
}
