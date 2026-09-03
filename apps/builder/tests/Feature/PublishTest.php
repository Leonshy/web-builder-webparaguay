<?php

namespace Tests\Feature;

use App\Generation\SiteRuntimeClient;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeSiteRuntimeClient;
use Tests\TestCase;

class PublishTest extends TestCase
{
    use RefreshDatabase;

    private function generatedProject(): Project
    {
        $org = Organization::create(['name' => 'Org', 'billing_phone' => '+595 981 000 000', 'billing_address' => 'Palma 123', 'billing_city' => 'Asunción', 'billing_country' => 'PY', 'billing_tax_id' => '80012345-6']);
        $user = $org->users()->create(['name' => 'Juan Metal', 'email' => 'juan@e.com.py', 'password' => bcrypt('x')]);
        $user->markEmailAsVerified();
        $this->actingAs($user);
        $project = $org->projects()->create(['user_id' => $user->id, 'name' => 'Sitio', 'status' => 'generated']);
        $project->site()->create(['name' => 'Talleres Yvytu', 'runtime_site_ref' => '7', 'preview_url' => 'http://rt/s/x', 'document' => json_decode((string) file_get_contents(\Webparaguay\Schema\Schema::examplePath()), true)]);

        $this->app->singleton(SiteRuntimeClient::class, FakeSiteRuntimeClient::class);

        return $project;
    }

    public function test_publicar_con_subdominio_cobra_y_deja_el_sitio_en_linea(): void
    {
        $project = $this->generatedProject();

        $this->post(route('publish.store', $project), [
            'plan' => 'web', 'domain_kind' => 'subdomain',
        ])->assertRedirect();

        $project->refresh();
        $this->assertSame('published', $project->status);
        $this->assertSame('subdomain_live', $project->site->domain_status);
        $this->assertStringEndsWith('.'.config('publishing.subdomain_base'), $project->site->live_fqdn);
        $this->assertSame('0.1.0', $project->site->runtime_version);
        $this->assertDatabaseHas('payments', ['project_id' => $project->id, 'status' => 'paid']);

        // Se generan credenciales del CMS y se pasan a la instancia.
        $this->assertSame('juan@e.com.py', $project->site->cms_email);
        $this->assertNotEmpty($project->site->cms_password);
        $fake = $this->app->make(SiteRuntimeClient::class);
        $this->assertSame('juan@e.com.py', $fake->created[0]['ownerEmail']);
        $this->assertSame($project->site->cms_password, $fake->created[0]['ownerPassword']);
    }

    public function test_com_py_publica_en_subdominio_y_abre_tarea_de_backoffice(): void
    {
        $project = $this->generatedProject();

        $this->post(route('publish.store', $project), [
            'plan' => 'web', 'domain_kind' => 'compy', 'domain_value' => 'talleresyvytu.com.py',
        ])->assertRedirect();

        $project->refresh();
        $this->assertSame('published', $project->status);
        $this->assertSame('compy_pending', $project->site->domain_status);
        $this->assertSame('talleresyvytu.com.py', $project->site->pending_fqdn);
        $this->assertDatabaseHas('backoffice_tasks', ['project_id' => $project->id, 'kind' => 'domain_compy_register', 'status' => 'open']);
    }

    public function test_no_se_puede_publicar_un_sitio_no_generado(): void
    {
        $org = Organization::create(['name' => 'O', 'billing_phone' => '1', 'billing_address' => 'a', 'billing_city' => 'c', 'billing_country' => 'PY', 'billing_tax_id' => 'x']);
        $user = $org->users()->create(['name' => 'U', 'email' => 'u@e.com', 'password' => bcrypt('x')]);
        $user->markEmailAsVerified();
        $this->actingAs($user);
        $project = $org->projects()->create(['user_id' => $user->id, 'name' => 'P', 'status' => 'draft']);

        $this->post(route('publish.store', $project), ['plan' => 'web', 'domain_kind' => 'subdomain'])
            ->assertStatus(422);
    }

    public function test_no_se_republica_un_sitio_ya_publicado(): void
    {
        $project = $this->generatedProject();
        $this->post(route('publish.store', $project), ['plan' => 'web', 'domain_kind' => 'subdomain']);

        $this->post(route('publish.store', $project->fresh()), ['plan' => 'web', 'domain_kind' => 'subdomain'])
            ->assertStatus(422);

        $this->assertSame(1, $project->payments()->count());
    }

    public function test_pago_pendiente_deja_el_proyecto_esperando_activacion(): void
    {
        $this->app->bind(\Webparaguay\Provisioning\SitePublisher::class, fn () => new class implements \Webparaguay\Provisioning\SitePublisher
        {
            public function publish(\Webparaguay\Provisioning\PublishInput $input): \Webparaguay\Provisioning\PublishResult
            {
                return new \Webparaguay\Provisioning\PublishResult(
                    charge: new \Webparaguay\Provisioning\Charge(\Webparaguay\Provisioning\Charge::PENDING, 'inv_1', $input->plan->price, note: 'confirmar en WHMCS'),
                    account: new \Webparaguay\Provisioning\HostingAccount('svc_9', $input->subdomainLabel.'.webparaguay.com'),
                    domain: new \Webparaguay\Provisioning\DomainOutcome(\Webparaguay\Provisioning\DomainOutcome::SUBDOMAIN_LIVE, $input->subdomainLabel.'.webparaguay.com'),
                    runtimeVersion: '0.1.0', orderRef: 'ord_5', serviceRef: 'svc_9', awaitingActivation: true,
                );
            }
        });

        $project = $this->generatedProject();
        $project->site->update(['document' => json_decode((string) file_get_contents(\Webparaguay\Schema\Schema::examplePath()), true)]);

        $this->post(route('publish.store', $project), ['plan' => 'web', 'domain_kind' => 'subdomain'])->assertRedirect();

        $project->refresh();
        $this->assertSame('awaiting_payment', $project->status);
        $this->assertDatabaseHas('payments', ['project_id' => $project->id, 'status' => 'pending']);
        $this->assertDatabaseHas('backoffice_tasks', ['project_id' => $project->id, 'kind' => 'confirm_order_payment']);
        $this->assertNull($project->site->published_at);

        // Activar con el pago ya confirmado (driver fake → no chequea WHMCS).
        app(\App\Publishing\ActivateOrder::class)->handle($project);

        $project->refresh();
        $this->assertSame('published', $project->status);
        $this->assertSame('done', $project->backofficeTasks()->where('kind', 'confirm_order_payment')->value('status'));
        $this->assertDatabaseHas('payments', ['project_id' => $project->id, 'status' => 'paid']);
        $this->assertNotNull($project->site->published_at);
    }
}
