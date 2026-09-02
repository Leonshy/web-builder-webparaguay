<?php

namespace Tests\Feature;

use App\Models\Cms\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Webparaguay\Schema\Schema;

class InternalCreateSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['site-runtime.internal_token' => 'secreto-de-prueba']);
    }

    private function doc(): array
    {
        return json_decode((string) file_get_contents(Schema::examplePath()), true);
    }

    public function test_crea_el_sitio_desde_el_documento_y_devuelve_preview(): void
    {
        $res = $this->withToken('secreto-de-prueba')
            ->postJson('/internal/sites', [
                'builder_project_ref' => 'proj-42',
                'name' => 'Talleres Yvytu',
                'document' => $this->doc(),
            ])
            ->assertCreated()
            ->assertJsonStructure(['site_ref', 'preview_url']);

        $site = Site::where('builder_project_ref', 'proj-42')->firstOrFail();
        $this->assertSame(2, $site->pages()->count());

        // El preview devuelto funciona.
        $this->get($res->json('preview_url'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_reimportar_el_mismo_proyecto_reemplaza_el_contenido(): void
    {
        $payload = ['builder_project_ref' => 'p1', 'name' => 'X', 'document' => $this->doc()];
        $this->withToken('secreto-de-prueba')->postJson('/internal/sites', $payload)->assertCreated();
        $this->withToken('secreto-de-prueba')->postJson('/internal/sites', $payload)->assertCreated();

        $this->assertSame(1, Site::where('builder_project_ref', 'p1')->count());
    }

    public function test_rechaza_sin_token(): void
    {
        $this->postJson('/internal/sites', ['builder_project_ref' => 'x', 'name' => 'x', 'document' => $this->doc()])
            ->assertStatus(401);
    }

    public function test_rechaza_un_documento_invalido(): void
    {
        $this->withToken('secreto-de-prueba')
            ->postJson('/internal/sites', ['builder_project_ref' => 'x', 'name' => 'x', 'document' => ['pages' => []]])
            ->assertStatus(422);
    }
}
