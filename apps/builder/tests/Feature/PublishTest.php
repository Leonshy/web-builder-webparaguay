<?php

namespace Tests\Feature;

use App\Generation\SiteRuntimeClient;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishTest extends TestCase
{
    use RefreshDatabase;

    private function generatedProject(): Project
    {
        $org = Organization::create(['name' => 'Org']);
        $user = $org->users()->create(['name' => 'Juan Metal', 'email' => 'juan@e.com.py', 'password' => bcrypt('x')]);
        $project = $org->projects()->create(['user_id' => $user->id, 'name' => 'Sitio', 'status' => 'generated']);
        $project->site()->create(['name' => 'Talleres Yvytu', 'runtime_site_ref' => '7', 'preview_url' => 'http://rt/s/x']);

        // El markPublished no debe salir a la red en tests.
        $this->app->bind(SiteRuntimeClient::class, fn () => new class implements SiteRuntimeClient
        {
            public function createSite(Project $p, string $n, array $d): array
            {
                return ['site_ref' => '7', 'preview_url' => 'x'];
            }

            public function markPublished(string $siteRef, string $fqdn): void {}
        });

        return $project;
    }

    public function test_publicar_con_subdominio_cobra_y_deja_el_sitio_en_linea(): void
    {
        $project = $this->generatedProject();

        $this->post(route('publish.store', $project), [
            'plan' => 'basico', 'domain_kind' => 'subdomain',
        ])->assertRedirect();

        $project->refresh();
        $this->assertSame('published', $project->status);
        $this->assertSame('subdomain_live', $project->site->domain_status);
        $this->assertStringEndsWith('.webparaguay.com', $project->site->live_fqdn);
        $this->assertSame('0.1.0', $project->site->runtime_version);
        $this->assertDatabaseHas('payments', ['project_id' => $project->id, 'status' => 'paid']);
    }

    public function test_com_py_publica_en_subdominio_y_abre_tarea_de_backoffice(): void
    {
        $project = $this->generatedProject();

        $this->post(route('publish.store', $project), [
            'plan' => 'profesional', 'domain_kind' => 'compy', 'domain_value' => 'talleresyvytu.com.py',
        ])->assertRedirect();

        $project->refresh();
        $this->assertSame('published', $project->status);
        $this->assertSame('compy_pending', $project->site->domain_status);
        $this->assertSame('talleresyvytu.com.py', $project->site->pending_fqdn);
        $this->assertDatabaseHas('backoffice_tasks', ['project_id' => $project->id, 'kind' => 'domain_compy_register', 'status' => 'open']);
    }

    public function test_no_se_puede_publicar_un_sitio_no_generado(): void
    {
        $org = Organization::create(['name' => 'O']);
        $user = $org->users()->create(['name' => 'U', 'email' => 'u@e.com', 'password' => bcrypt('x')]);
        $project = $org->projects()->create(['user_id' => $user->id, 'name' => 'P', 'status' => 'draft']);

        $this->post(route('publish.store', $project), ['plan' => 'basico', 'domain_kind' => 'subdomain'])
            ->assertStatus(422);
    }

    public function test_no_se_republica_un_sitio_ya_publicado(): void
    {
        $project = $this->generatedProject();
        $this->post(route('publish.store', $project), ['plan' => 'basico', 'domain_kind' => 'subdomain']);

        $this->post(route('publish.store', $project->fresh()), ['plan' => 'basico', 'domain_kind' => 'subdomain'])
            ->assertStatus(422);

        $this->assertSame(1, $project->payments()->count());
    }
}
