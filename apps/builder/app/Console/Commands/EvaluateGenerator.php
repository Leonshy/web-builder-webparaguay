<?php

namespace App\Console\Commands;

use App\Generation\Brief;
use App\Generation\Generator;
use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Webparaguay\Schema\SchemaValidator;

/**
 * Harness de validación del generador.
 *
 *   php artisan builder:eval-generator --driver=claude --runs=3
 *
 * Corre varios briefs contra el generador y verifica que la salida valida
 * contra site.schema.json. Reporta tasa de éxito, errores comunes y costo.
 * Si el generador con IA no valida de forma consistente, se ajusta el PROMPT,
 * nunca el esquema.
 */
class EvaluateGenerator extends Command
{
    protected $signature = 'builder:eval-generator {--driver= : template|claude (default: config)} {--runs=3} {--min-pass=0.9}';

    protected $description = 'Valida N generaciones contra el esquema y reporta';

    public function handle(SchemaValidator $validator): int
    {
        if ($driver = $this->option('driver')) {
            Config::set('generation.driver', $driver);
            $this->components->info("Driver: {$driver}");
        }

        $runs = (int) $this->option('runs');
        $org = Organization::firstOrCreate(['name' => '[eval] generador']);
        $generator = app(Generator::class);

        $total = 0;
        $ok = 0;
        $errorTally = [];
        $costMicro = 0;
        $rows = [];

        foreach ($this->briefs() as $name => $brief) {
            for ($i = 1; $i <= $runs; $i++) {
                $total++;
                try {
                    $doc = $generator->generate($brief, $org, null);
                    $errors = $validator->errors($doc);
                } catch (\Throwable $e) {
                    $errors = ['EXCEPCIÓN: '.$e->getMessage()];
                    $doc = [];
                }

                $valid = $errors === [];
                $ok += $valid ? 1 : 0;
                foreach ($errors as $err) {
                    $key = preg_replace('/\d+/', 'N', explode(':', $err)[0]);
                    $errorTally[$key] = ($errorTally[$key] ?? 0) + 1;
                }

                $rows[] = [$name, "#{$i}", $valid ? 'OK' : 'FALLA', count($errors), $this->pages($doc)];
                if (! $valid) {
                    foreach (array_slice($errors, 0, 3) as $err) {
                        $this->line("  <fg=red>✗</> {$name} #{$i}: {$err}");
                    }
                }
            }
        }

        $costMicro = (int) $org->aiUsages()->sum('cost_microusd');

        $this->newLine();
        $this->table(['Brief', 'Run', 'Resultado', '# errores', '# páginas'], $rows);

        $rate = $total ? $ok / $total : 0;
        $this->newLine();
        $this->components->twoColumnDetail('Tasa de éxito', sprintf('%d/%d (%.0f%%)', $ok, $total, $rate * 100));
        if ($costMicro > 0) {
            $this->components->twoColumnDetail('Costo total', sprintf('US$ %.4f (US$ %.4f por generación)', $costMicro / 1e6, $costMicro / 1e6 / max($total, 1)));
        }
        if ($errorTally) {
            $this->newLine();
            $this->line('Errores más comunes:');
            arsort($errorTally);
            foreach ($errorTally as $err => $n) {
                $this->line("  {$n}×  {$err}");
            }
        }

        if ($rate < (float) $this->option('min-pass')) {
            $this->components->error(sprintf('Tasa por debajo del mínimo (%.0f%%). Ajustar el prompt.', $this->option('min-pass') * 100));

            return self::FAILURE;
        }

        $this->components->info('Generador dentro del umbral.');

        return self::SUCCESS;
    }

    /** @param array<string,mixed> $doc */
    private function pages(array $doc): int
    {
        return count($doc['pages'] ?? []);
    }

    /** @return array<string,Brief> */
    private function briefs(): array
    {
        $theme = fn (string $pairing) => [
            'colors' => ['primary' => '#1f6f5c', 'accent' => '#e0a24b', 'background' => '#fbfaf7', 'text' => '#1a1a1a'],
            'typography' => ['pairing' => $pairing],
        ];

        return [
            'metalurgica-institucional' => new Brief(
                template: 'institucional', goal: 'contact', theme: $theme('space-grotesk-ibm-plex'),
                settings: ['business_name' => 'Talleres Yvytu', 'tagline' => 'Metalurgia de precisión en Paraguay', 'industry' => 'Metalurgia', 'phone' => '+595 21 000 000', 'whatsapp' => '+595 981 000 000', 'email' => 'contacto@ejemplo.com.py', 'address' => 'Ruta Transchaco km 12', 'schedule' => 'Lun a Vie 7 a 17'],
                services: [['name' => 'Mecanizado CNC', 'description' => 'Torno y fresa con control numérico para series y prototipos.'], ['name' => 'Corte y plegado', 'description' => 'Chapa de hasta 12 mm.'], ['name' => 'Soldadura especializada', 'description' => 'MIG, TIG y arco para acero, inoxidable y aluminio.']],
                aboutText: "Empezamos en 2004 como un taller de dos personas.\n\nHoy somos veinticinco y fabricamos para plantas industriales de todo el país.",
            ),
            'consultorio-landing' => new Brief(
                template: 'landing', goal: 'contact', theme: $theme('libre-franklin-lora'),
                settings: ['business_name' => 'Consultorio Ñande', 'tagline' => 'Kinesiología y rehabilitación', 'industry' => 'Salud', 'phone' => '+595 21 111 111', 'whatsapp' => '+595 982 111 111', 'email' => 'turnos@ejemplo.com.py', 'schedule' => 'Lun a Sáb 8 a 20'],
                services: [['name' => 'Rehabilitación deportiva'], ['name' => 'Fisioterapia post operatoria'], ['name' => 'Reeducación postural']],
                aboutText: 'Atendemos con turno programado, sin salas de espera llenas.',
            ),
            'restaurante-landing-sin-about' => new Brief(
                template: 'landing', goal: 'about', theme: $theme('dm-serif-dm-sans'),
                settings: ['business_name' => 'Rincón del Chipa', 'industry' => 'Gastronomía', 'whatsapp' => '+595 983 222 222', 'address' => 'Palma casi Chile', 'schedule' => 'Todos los días 6 a 13'],
                services: [['name' => 'Chipa artesanal'], ['name' => 'Sopa paraguaya'], ['name' => 'Cocido a leña']],
            ),
            'servicios-minimo' => new Brief(
                template: 'landing', goal: 'contact', theme: $theme('manrope'),
                settings: ['business_name' => 'Estudio Contable López'],
            ),
        ];
    }
}
