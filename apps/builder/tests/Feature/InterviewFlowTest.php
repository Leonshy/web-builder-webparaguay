<?php

namespace Tests\Feature;

use App\Generation\Brief;
use App\Generation\Generator;
use App\Generation\PaletteProposer;
use App\Generation\SiteRuntimeClient;
use App\Generation\TemplateGenerator;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeSiteRuntimeClient;
use Tests\TestCase;
use Webparaguay\Schema\SchemaValidator;

class InterviewFlowTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        $org = Organization::create(['name' => 'Org']);
        $user = $org->users()->create(['name' => 'U', 'email' => 'u@e.com', 'password' => bcrypt('x')]);
        $user->markEmailAsVerified();
        $this->actingAs($user);

        return $org->projects()->create(['user_id' => $user->id, 'name' => 'Sitio']);
    }

    private function fakeRuntime(): void
    {
        $this->app->bind(SiteRuntimeClient::class, FakeSiteRuntimeClient::class);
    }

    public function test_cada_etapa_persiste_y_volver_atras_no_pierde_nada(): void
    {
        $project = $this->project();

        $this->post(route('interview.purpose', $project), [
            'industry' => 'Metalurgia', 'audience' => 'Industrias', 'goal' => 'contact', 'template' => 'landing',
        ])->assertRedirect();

        $this->post(route('interview.brand', $project), [
            'primary' => '#1f6f5c', 'accent' => '#e0a24b', 'background' => '#fbfaf7', 'text' => '#1a1a1a',
            'pairing' => 'playfair-inter', 'source' => 'palette',
        ])->assertRedirect();

        $draft = $project->draft()->refresh();
        $this->assertSame('confirmed', $draft->purpose_status);
        $this->assertSame('confirmed', $draft->brand_status);

        // Reabrir marca y cambiarla marca... no hay etapas confirmadas después todavía.
        $this->post(route('interview.reopen', ['project' => $project, 'stage' => 'purpose']));
        $draft->refresh();
        $this->assertSame('Metalurgia', $draft->purpose['industry']); // no se perdió
    }

    public function test_propuesta_de_paletas_no_traba_la_etapa_1(): void
    {
        $proposals = (new PaletteProposer())->propose('Taller metalúrgico');
        $this->assertGreaterThanOrEqual(2, count($proposals));
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $proposals[0]['colors']['primary']);
    }

    public function test_el_generador_por_plantilla_produce_un_documento_valido(): void
    {
        $brief = new Brief(
            template: 'institucional',
            goal: 'contact',
            theme: ['colors' => ['primary' => '#1f6f5c', 'accent' => '#e0a24b', 'background' => '#fbfaf7', 'text' => '#1a1a1a'], 'typography' => ['pairing' => 'sora-inter']],
            settings: ['business_name' => 'Talleres Yvytu', 'tagline' => 'Metalurgia de precisión', 'industry' => 'Metalurgia', 'phone' => '+595 21 000 000', 'email' => 'a@b.com.py'],
            services: [['name' => 'Mecanizado CNC', 'description' => 'Torno y fresa CNC.'], ['name' => 'Soldadura']],
            aboutText: "Empezamos en 2004.\n\nHoy somos 25 personas.",
        );

        $org = Organization::create(['name' => 'O']);
        $doc = (new TemplateGenerator())->generate($brief, $org, null);

        $this->assertSame([], (new SchemaValidator())->errors($doc));
        $this->assertSame('institucional', $doc['template']);
        $this->assertContains('nosotros', array_column($doc['pages'], 'slug'));
    }

    public function test_el_generador_por_plantilla_valida_aun_sin_servicios_ni_about(): void
    {
        $brief = new Brief(
            template: 'landing', goal: 'contact',
            theme: ['colors' => [], 'typography' => ['pairing' => 'manrope']],
            settings: ['business_name' => 'Comercio X'],
        );
        $doc = (new TemplateGenerator())->generate($brief, Organization::create(['name' => 'O']), null);

        $this->assertSame([], (new SchemaValidator())->errors($doc));
    }

    public function test_flujo_completo_genera_el_sitio_y_hace_el_handoff(): void
    {
        $this->fakeRuntime();
        $project = $this->project();

        $this->post(route('interview.purpose', $project), ['industry' => 'Metalurgia', 'goal' => 'contact', 'template' => 'landing']);
        $this->post(route('interview.brand', $project), [
            'primary' => '#1f6f5c', 'accent' => '#e0a24b', 'background' => '#fbfaf7', 'text' => '#1a1a1a', 'pairing' => 'manrope', 'source' => 'palette',
        ]);
        $this->post(route('interview.content', $project), [
            'business_name' => 'Talleres Yvytu',
            'services_raw' => "Mecanizado: Torno CNC\nSoldadura: MIG y TIG",
            'phone' => '+595 21 000 000',
        ]);

        $this->post(route('interview.generate', $project))->assertRedirect(route('interview.result', $project));

        $project->refresh();
        $this->assertSame('generated', $project->status);
        $this->assertSame('done', $project->draft()->refresh()->stage);
        $this->assertSame('rt_'.$project->id, $project->site->runtime_site_ref);
    }

    public function test_si_el_documento_no_valida_el_proyecto_queda_en_needs_fix(): void
    {
        $this->fakeRuntime();
        $this->app->bind(Generator::class, fn () => new class implements Generator
        {
            public function generate(Brief $b, Organization $o, ?Project $p): array
            {
                return ['schema_version' => '0.1', 'theme' => [], 'settings' => [], 'pages' => []];
            }

            public function repair(Brief $b, array $d, array $e, Organization $o, ?Project $p): array
            {
                return $d;
            }
        });

        $project = $this->project();
        $this->post(route('interview.purpose', $project), ['industry' => 'X', 'goal' => 'contact', 'template' => 'landing']);
        $this->post(route('interview.brand', $project), ['primary' => '#111111', 'accent' => '#222222', 'background' => '#eeeeee', 'text' => '#000000', 'pairing' => 'manrope', 'source' => 'palette']);
        $this->post(route('interview.content', $project), ['business_name' => 'X']);

        $this->post(route('interview.generate', $project))->assertRedirect();

        $this->assertSame('needs_fix', $project->draft()->refresh()->stage);
        $this->assertNull($project->refresh()->site);
    }
}
