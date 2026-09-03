<?php

namespace App\Generation;

use App\Models\Project;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class HttpSiteRuntimeClient implements SiteRuntimeClient
{
    public function createSite(
        Project $project,
        string $name,
        array $document,
        ?string $baseUrl = null,
        ?string $ownerEmail = null,
        ?string $ownerPassword = null,
        ?string $ownerName = null,
    ): array {
        $response = Http::withToken((string) config('generation.site_runtime_token'))
            ->timeout(30)
            ->post("{$this->base($baseUrl)}/internal/sites", array_filter([
                'builder_project_ref' => (string) $project->id,
                'name' => $name,
                'document' => $document,
                'owner_email' => $ownerEmail,
                'owner_password' => $ownerPassword,
                'owner_name' => $ownerName,
            ], fn ($v) => $v !== null))
            ->throw();

        $data = $this->decode($response);

        return [
            'site_ref' => (string) ($data['site_ref'] ?? ''),
            'preview_url' => (string) ($data['preview_url'] ?? ''),
        ];
    }

    /**
     * Decodifica el JSON de la respuesta tolerando basura al principio del
     * cuerpo (el server de desarrollo `artisan serve` a veces antepone un
     * Notice de "Broken pipe"). En producción el cuerpo es JSON puro.
     *
     * @return array<string,mixed>
     */
    private function decode(Response $response): array
    {
        $body = $response->body();
        $data = json_decode($body, true);

        if (! is_array($data) && preg_match('/\{.*\}/s', $body, $m)) {
            $data = json_decode($m[0], true);
        }

        if (! is_array($data)) {
            throw new RuntimeException('site-runtime devolvió una respuesta no interpretable: '.mb_substr($body, 0, 300));
        }

        return $data;
    }

    public function markPublished(string $siteRef, string $fqdn, ?string $baseUrl = null): void
    {
        Http::withToken((string) config('generation.site_runtime_token'))
            ->timeout(30)
            ->post("{$this->base($baseUrl)}/internal/sites/{$siteRef}/publish", ['fqdn' => $fqdn])
            ->throw();
    }

    private function base(?string $baseUrl): string
    {
        return rtrim($baseUrl ?: (string) config('generation.site_runtime_url'), '/');
    }
}
