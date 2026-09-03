<?php

namespace App\Generation;

use App\Models\Project;
use Illuminate\Support\Facades\Http;

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
            ->throw()
            ->json();

        return [
            'site_ref' => (string) $response['site_ref'],
            'preview_url' => (string) $response['preview_url'],
        ];
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
