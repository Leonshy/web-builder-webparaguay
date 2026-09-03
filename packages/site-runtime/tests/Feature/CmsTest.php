<?php

namespace Tests\Feature;

use App\Cms\SiteAssembler;
use App\Models\Cms\Section;
use App\Models\Cms\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Webparaguay\Schema\Schema;
use Webparaguay\Schema\SchemaValidator;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function seedSite(): Site
    {
        $doc = json_decode((string) file_get_contents(Schema::examplePath()), true);
        $site = Site::create(['name' => 'Test', 'template' => $doc['template'], 'theme' => $doc['theme'], 'settings' => $doc['settings']]);

        return (new SiteAssembler())->importInto($site, $doc);
    }

    public function test_el_sitio_sembrado_desde_la_base_valida_y_renderiza_igual_que_el_archivo(): void
    {
        $site = $this->seedSite();
        $doc = (new SiteAssembler())->toArray($site->load('pages.sections'));

        $this->assertSame([], (new SchemaValidator())->errors($doc));
        $this->assertSame(2, $site->pages()->count());
    }

    public function test_cambiar_de_variante_conserva_todo_el_contenido(): void
    {
        $site = $this->seedSite();
        /** @var Section $hero */
        $hero = Section::where('type', 'hero')->firstOrFail();
        $contentAntes = $hero->content;

        $this->put(route('cms.section.variant', $hero), ['variant' => 'minimal'])
            ->assertRedirect();

        $hero->refresh();
        $this->assertSame('minimal', $hero->variant);
        // 'minimal' no usa image ni badge, pero siguen guardados.
        $this->assertSame($contentAntes, $hero->content);
        $this->assertArrayHasKey('image', $hero->content);
    }

    public function test_una_escritura_que_rompe_el_esquema_no_se_guarda(): void
    {
        $site = $this->seedSite();
        $hero = Section::where('type', 'hero')->firstOrFail();

        // headline supera el máximo de 70 → el documento no valida.
        $this->put(route('cms.section.update', $hero), [
            'is_active' => '1',
            'envelope' => ['background' => 'default'],
            'content' => ['headline' => str_repeat('x', 200)],
        ])->assertSessionHasErrors('schema');

        $hero->refresh();
        $this->assertNotSame(str_repeat('x', 200), $hero->content['headline'] ?? null);
    }

    public function test_agregar_pagina_crea_una_seccion_inicial_valida(): void
    {
        $site = $this->seedSite();

        $this->post(route('cms.site.pages.store', $site), ['title' => 'Servicios', 'slug' => 'servicios'])
            ->assertRedirect();

        $page = $site->pages()->where('slug', 'servicios')->firstOrFail();
        $this->assertSame(1, $page->sections()->count());
        $this->assertSame([], (new SchemaValidator())->errors((new SiteAssembler())->toArray($site->fresh()->load('pages.sections'))));
    }

    public function test_el_preview_con_token_renderiza_desde_la_base_y_es_no_indexable(): void
    {
        $site = $this->seedSite();
        $token = $site->previewTokens()->create(['expires_at' => now()->addDay()]);

        $res = $this->get("/s/{$token->token}")
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->assertStringContainsString('Piezas metalicas que aguantan el trabajo pesado', $res->getContent());
    }

    public function test_un_token_de_preview_vencido_da_410(): void
    {
        $site = $this->seedSite();
        $token = $site->previewTokens()->create(['expires_at' => now()->subDay()]);

        $this->get("/s/{$token->token}")->assertStatus(410);
    }

    public function test_el_formulario_de_seccion_se_deriva_del_esquema(): void
    {
        $site = $this->seedSite();
        $faq = Section::where('type', 'faq')->firstOrFail();

        $html = $this->get(route('cms.section', $faq))->assertOk()->getContent();

        // faq: items es lista de objetos {question, answer}
        $this->assertStringContainsString('name="content[items][0][question]"', $html);
        $this->assertStringContainsString('name="content[items][0][answer]"', $html);
    }
}
