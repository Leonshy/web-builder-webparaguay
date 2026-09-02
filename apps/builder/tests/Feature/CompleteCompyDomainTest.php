<?php

namespace Tests\Feature;

use App\Generation\SiteRuntimeClient;
use App\Models\Organization;
use App\Models\Project;
use App\Publishing\CompleteCompyDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteCompyDomainTest extends TestCase
{
    use RefreshDatabase;

    private function pendingCompyProject(): Project
    {
        $this->app->bind(SiteRuntimeClient::class, fn () => new class implements SiteRuntimeClient
        {
            public function createSite(Project $p, string $n, array $d): array
            {
                return ['site_ref' => '5', 'preview_url' => 'x'];
            }

            public function markPublished(string $siteRef, string $fqdn): void {}
        });

        $org = Organization::create(['name' => 'Org', 'plan' => 'paid']);
        $user = $org->users()->create(['name' => 'U', 'email' => 'u@e.com', 'password' => bcrypt('x')]);
        $project = $org->projects()->create(['user_id' => $user->id, 'name' => 'P', 'status' => 'published']);
        $project->site()->create([
            'name' => 'Talleres Yvytu', 'runtime_site_ref' => '5', 'hosting_account_ref' => 'acct_x',
            'live_fqdn' => 'talleresyvytu5.webparaguay.com', 'published_domain' => 'talleresyvytu5.webparaguay.com',
            'domain_status' => 'compy_pending', 'pending_fqdn' => 'talleresyvytu.com.py',
        ]);
        $project->backofficeTasks()->create(['kind' => 'domain_compy_register', 'note' => 'Registrar en NIC.py']);

        return $project;
    }

    public function test_cerrar_el_tramite_reapunta_el_dominio_y_cierra_la_tarea(): void
    {
        $project = $this->pendingCompyProject();
        $task = $project->backofficeTasks()->first();

        app(CompleteCompyDomain::class)->handle($task);

        $project->refresh();
        $this->assertSame('compy_live', $project->site->domain_status);
        $this->assertSame('talleresyvytu.com.py', $project->site->live_fqdn);
        $this->assertNull($project->site->pending_fqdn);
        $this->assertSame('done', $task->refresh()->status);
    }

    public function test_el_comando_de_backoffice_corre(): void
    {
        $project = $this->pendingCompyProject();
        $taskId = $project->backofficeTasks()->first()->id;

        $this->artisan("builder:complete-domain-task {$taskId}")->assertExitCode(0);
    }

    public function test_no_cierra_una_tarea_que_no_es_de_dominio(): void
    {
        $project = $this->pendingCompyProject();
        $other = $project->backofficeTasks()->create(['kind' => 'generation_review', 'note' => 'x']);

        $this->expectException(\RuntimeException::class);
        app(CompleteCompyDomain::class)->handle($other);
    }
}
