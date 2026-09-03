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
        ])->timeout(180)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 8000,
            // El contrato es grande y estable: se cachea entre generaciones.
            'system' => [[
                'type' => 'text',
                'text' => $this->systemPrompt(),
                'cache_control' => ['type' => 'ephemeral'],
            ]],
            'messages' => [['role' => 'user', 'content' => $userContent]],
        ])->throw()->json();

        $usage = $response['usage'] ?? [];
        $this->usage->record(
            $organization, $project, $action, $model,
            (int) ($usage['input_tokens'] ?? 0)
                + (int) ($usage['cache_creation_input_tokens'] ?? 0)
                + (int) ($usage['cache_read_input_tokens'] ?? 0),
            (int) ($usage['output_tokens'] ?? 0),
        );

        $text = collect($response['content'] ?? [])->firstWhere('type', 'text')['text'] ?? '';

        return $this->sanitize($this->extractJson($text));
    }

    /**
     * Red de seguridad: el modelo a veces deja `image` con `src: ""` (placeholder
     * que no valida). Como `image` es opcional en todo el contrato, se descarta
     * cualquier objeto imagen sin `src` real.
     *
     * @param  array<string,mixed>  $node
     * @return array<string,mixed>
     */
    private function sanitize(array $node): array
    {
        foreach ($node as $key => $value) {
            if (! is_array($value)) {
                continue;
            }
            if ($key === 'image' && trim((string) ($value['src'] ?? '')) === '') {
                unset($node[$key]);

                continue;
            }
            $node[$key] = $this->sanitize($value);
        }

        return $node;
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
        $example = json_encode(
            json_decode((string) file_get_contents(Schema::examplePath()), true),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        );
        $variants = $this->variantCheatSheet();
        $icons = implode(', ', Schema::decoded()['$defs']['icon']['enum']);

        return <<<PROMPT
        Sos un configurador de sitios web para PYMES paraguayas. NUNCA escribís
        código ni HTML de layout: producís UN ÚNICO objeto JSON que valida
        EXACTAMENTE contra este JSON Schema.

        <schema>
        {$schema}
        </schema>

        VARIANTES VÁLIDAS POR TIPO (elegí sólo de acá):
        {$variants}

        ÍCONOS VÁLIDOS (clave `icon`, elegí sólo de acá):
        {$icons}

        REGLAS DURAS (una violación invalida toda la salida):
        1. Sólo campos del contrato. Un campo desconocido invalida la salida.
        2. Nunca excedas los `maxLength` / `maxItems` / `minItems`.
        3. `variant` sólo de la lista de arriba para ese `type`.
        4. `content` debe cumplir el sub-esquema del tipo (`content_<type>` en
           `\$defs`), con sus campos `required`.
        5. Un valor omitido es válido; uno inventado no. Si falta información en
           el brief, omití el campo opcional. Prohibido inventar datos de
           contacto, teléfonos, direcciones o testimonios.
        6. `theme.colors`: SÓLO los 4 del brief (primary, accent, background,
           text). No agregues los derivados.
        7. `theme.typography.pairing`: exactamente el string del brief.
        8. No repitas el `title` del envelope dentro de `content`.
        9. Todo el texto en español paraguayo neutro, claro y concreto.
        10. NUNCA inventes imágenes. Si no tenés una URL real de imagen, OMITÍ
            por completo el objeto `image` (no lo incluyas con `src` vacío) y
            elegí una `variant` que no dependa de imagen (ej. hero `minimal` /
            `centered`, media_text sin imagen). `src` nunca puede ser "".
            Cuando SÍ haya imagen, `image.alt` es obligatorio y descriptivo
            (qué se ve), nunca el nombre de archivo.
        11. `richtext` (campos `body`, `answer`): sólo `<p> <strong> <em> <ul>
            <ol> <li> <a> <br> <h3> <h4>`.
        12. `entity_grid` sólo con `source: "manual"` y `items`.
        13. `video.video_url` sólo YouTube o Vimeo.
        14. Cada página necesita ≥1 sección. La home lleva un `hero`. Las
            páginas interiores empiezan con `page_header`.

        NO generes: navegación, breadcrumb, sitemap. Los deriva el renderer.
        `layout.navbar` / `layout.footer` sí van, con sus variantes.

        EJEMPLO de un documento VÁLIDO (misma forma, otro rubro):
        <ejemplo>
        {$example}
        </ejemplo>

        Salida: SÓLO el objeto JSON, sin ```json, sin texto antes ni después.
        PROMPT;
    }

    private function variantCheatSheet(): string
    {
        $section = Schema::decoded()['$defs']['section']['allOf'] ?? [];
        $lines = [];
        foreach ($section as $branch) {
            $type = $branch['if']['properties']['type']['const'] ?? null;
            $vs = $branch['then']['properties']['variant']['enum'] ?? null;
            if ($type && $vs) {
                $lines[] = "  {$type}: ".implode(' | ', $vs);
            }
        }

        return implode("\n", $lines);
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
