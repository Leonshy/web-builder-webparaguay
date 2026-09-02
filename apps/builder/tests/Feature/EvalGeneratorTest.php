<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvalGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_harness_corre_y_el_generador_por_plantilla_pasa_el_umbral(): void
    {
        $this->artisan('builder:eval-generator --driver=template --runs=2')
            ->assertExitCode(0);
    }

    public function test_el_prompt_del_generador_con_ia_lista_todas_las_variantes(): void
    {
        $method = new \ReflectionMethod(\App\Generation\ClaudeGenerator::class, 'systemPrompt');
        $method->setAccessible(true);
        $prompt = $method->invoke(app(\App\Generation\ClaudeGenerator::class));

        // El cheat-sheet de variantes tiene que estar completo.
        foreach (['hero: split | fullbg | minimal | carousel', 'faq: accordion_single | accordion_two_col', 'entity_grid: card_full | card_compact | list'] as $line) {
            $this->assertStringContainsString($line, $prompt);
        }
        // Y el ejemplo válido.
        $this->assertStringContainsString('Talleres Yvytu', $prompt);
        $this->assertStringContainsString('SÓLO el objeto JSON', $prompt);
    }
}
