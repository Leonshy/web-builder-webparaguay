<?php

namespace App\Generation;

use App\Ai\AiUsageRecorder;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Webparaguay\Schema\Schema;

/**
 * Generador con IA por API de terceros (Anthropic). Misma interfaz que
 * TemplateGenerator: produce el JSON de configuración, nunca código.
 *
 * Toda llamada se registra en ai_usages (regla 19 del CLAUDE.md).
 * Requiere ANTHROPIC_API_KEY. Sin clave, se lanza una excepción clara y el
 * pipeline cae al generador determinístico según config.
 */
final class ClaudeGenerator implements Generator
{
    public function __construct(private AiUsageRecorder $usage) {}

    public function generate(Brief $brief, Organization $organization, ?Project $project): array
    {
        return $this->ask(
            action: 'site.generate',
            organization: $organization,
            project: $project,
            userContent: "BRIEF:\n".json_encode($this->briefPayload($brief), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                ."\n\nDevolvé SOLO el JSON del sitio, sin texto alrededor.",
        );
    }

    public function repair(Brief $brief, array $document, array $errors, Organization $organization, ?Project $project): array
    {
        return $this->ask(
            action: 'site.repair',
            organization: $organization,
            project: $project,
            userContent: "El siguiente JSON no valida contra el esquema.\n\nERRORES:\n - "
                .implode("\n - ", $errors)
                ."\n\nJSON:\n".json_encode($document, JSON_UNESCAPED_UNICODE)
                ."\n\nCorregí SOLO lo necesario y devolvé el JSON completo, sin texto alrededor.",
        );
    }

    /** @return array<string,mixed> */
    private function ask(string $action, Organization $organization, ?Project $project, string $userContent): array
    {
        $key = (string) config('services.anthropic.key');
        if ($key === '') {
            throw new RuntimeException('ANTHROPIC_API_KEY no está configurada; usá BUILDER_GENERATOR=template o cargá la clave.');
        }

        $model = (string) config('services.anthropic.model', 'claude-sonnet-5');

        $response = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 8000,
            'system' => $this->systemPrompt(),
            'messages' => [['role' => 'user', 'content' => $userContent]],
        ])->throw()->json();

        $usage = $response['usage'] ?? [];
        $this->usage->record(
            $organization, $project, $action, $model,
            (int) ($usage['input_tokens'] ?? 0),
            (int) ($usage['output_tokens'] ?? 0),
        );

        $text = collect($response['content'] ?? [])->firstWhere('type', 'text')['text'] ?? '';

        return $this->extractJson($text);
    }

    /** @return array<string,mixed> */
    private function extractJson(string $text): array
    {
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('La respuesta del modelo no contenía un JSON parseable.');
    }

    private function systemPrompt(): string
    {
        $schema = json_encode(Schema::decoded(), JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
        Sos un configurador de sitios. NUNCA escribís código: producís un único
        objeto JSON que valida EXACTAMENTE contra este JSON Schema:

        {$schema}

        Reglas (van literales):
        1. Nunca inventes campos. Sólo los del contrato.
        2. Nunca excedas los límites de longitud.
        3. Íconos sólo del enum `icon` del esquema.
        4. Variantes sólo de la lista de cada tipo.
        5. Un valor omitido es válido; uno inventado no.
        6. Colores: sólo los 4 obligatorios (primary, accent, background, text).
        7. Tipografía: sólo la clave `typography.pairing` que venga en el brief.
        8. No repitas el `title` del envelope dentro de `content`.
        9. Todo el texto en español paraguayo neutro.
        10. `alt` de imagen obligatorio y descriptivo; nunca el nombre del archivo.
        11. Prohibido copy de negocio real como relleno. Si falta info, omití el campo.

        La navegación, el breadcrumb y el layout global los deriva el renderer:
        no los generes. Devolvé SÓLO el JSON.
        PROMPT;
    }

    /** @return array<string,mixed> */
    private function briefPayload(Brief $brief): array
    {
        return [
            'template' => $brief->template,
            'goal' => $brief->goal,
            'theme' => $brief->theme,
            'settings' => $brief->settings,
            'services' => $brief->services,
            'about_text' => $brief->aboutText,
            'reference_texts' => $brief->referenceTexts,
        ];
    }
}
