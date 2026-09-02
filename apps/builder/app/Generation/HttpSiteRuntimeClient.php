<?php

namespace App\Generation;

use App\Models\Project;
use Illuminate\Support\Facades\Http;

final class HttpSiteRuntimeClient implements SiteRuntimeClient
{
    public function createSite(Project $project, string $name, array $document): array
    {
        $base = rtrim((string) config('generation.site_runtime_url'), '/');

        $response = Http::withToken((string) config('generation.site_runtime_token'))
            ->timeout(30)
            ->post("{$base}/internal/sites", [
                'builder_project_ref' => (string) $project->id,
                'name' => $name,
                'document' => $document,
            ])
            ->throw()
            ->json();

        return [
            'site_ref' => (string) $response['site_ref'],
            'preview_url' => (string) $response['preview_url'],
        ];
    }
}
