<?php

namespace Tests\Feature;

use App\Actions\RegisterAccount;
use App\Ai\AiUsageRecorder;
use App\Ai\ModelPricing;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_alta_crea_organizacion_y_un_solo_usuario(): void
    {
        $user = (new RegisterAccount())->handle('Talleres Yvytu', 'dueno@ejemplo.com.py', 'secreto-largo');

        $this->assertNotNull($user->organization);
        $this->assertSame(1, $user->organization->users()->count());
        $this->assertTrue($user->is($user->organization->owner()));
    }

    public function test_la_jerarquia_va_organizacion_usuario_proyecto_sitio(): void
    {
        $user = (new RegisterAccount())->handle('X', 'x@ejemplo.com.py', 'password-larga');
        $org = $user->organization;

        $project = $org->projects()->create(['user_id' => $user->id, 'name' => 'Sitio institucional']);
        $site = $project->site()->create(['name' => 'Talleres Yvytu']);

        $this->assertTrue($site->project->organization->is($org));
    }

    public function test_el_precio_se_calcula_por_modelo(): void
    {
        // Sonnet: 3 USD in / 15 USD out por millón.
        $micro = ModelPricing::costMicroUsd('claude-sonnet-5', 1_000_000, 1_000_000);
        $this->assertSame(18_000_000, $micro); // 18 USD en microUSD

        $this->assertSame(0, ModelPricing::costMicroUsd('claude-sonnet-5', 0, 0));
        $this->assertTrue(ModelPricing::isKnown('claude-sonnet-5'));
        $this->assertFalse(ModelPricing::isKnown('modelo-inventado'));
    }

    public function test_el_recorder_registra_cada_llamada_con_costo(): void
    {
        $user = (new RegisterAccount())->handle('X', 'x@ejemplo.com.py', 'password-larga');
        $org = $user->organization;
        $project = $org->projects()->create(['user_id' => $user->id, 'name' => 'P']);

        $recorder = new AiUsageRecorder();
        $recorder->record($org, $project, 'brand.palette', 'claude-sonnet-5', 1200, 800);
        $recorder->record($org, $project, 'copy.hero', 'claude-haiku-4-5', 500, 2000);

        $this->assertSame(2, $org->aiUsages()->count());
        $this->assertGreaterThan(0, $recorder->totalUsd($org));

        $first = $org->aiUsages()->first();
        $this->assertSame(1200, $first->input_tokens);
        $this->assertSame('brand.palette', $first->action);
        $this->assertNotNull($first->occurred_at);
    }

    public function test_la_pantalla_de_proyectos_muestra_el_consumo(): void
    {
        $this->get('/projects')->assertOk()->assertSee('consumo de IA');

        $this->post('/projects', ['name' => 'Mi sitio'])->assertRedirect();
        $this->assertDatabaseHas('projects', ['name' => 'Mi sitio']);
    }
}
