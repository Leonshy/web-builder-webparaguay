<?php

namespace Tests\Fakes;

use App\Generation\SiteRuntimeClient;
use App\Models\Project;
use Webparaguay\Schema\SchemaValidator;

/**
 * Fake del handoff a site-runtime para los tests. Valida el documento
 * (site-runtime nunca importaría uno inválido) y registra las llamadas.
 */
class FakeSiteRuntimeClient implements SiteRuntimeClient
{
    /** @var array<int,array{name:string,baseUrl:?string}> */
    public array $created = [];

    /** @var array<int,array{fqdn:string,baseUrl:?string}> */
    public array $published = [];

    public function createSite(Project $project, string $name, array $document, ?string $baseUrl = null): array
    {
        if ((new SchemaValidator())->errors($document) !== []) {
            throw new \RuntimeException('documento inválido llegó al runtime');
        }

        $this->created[] = ['name' => $name, 'baseUrl' => $baseUrl];

        return ['site_ref' => 'rt_'.$project->id, 'preview_url' => 'http://runtime.test/s/tok'];
    }

    public function markPublished(string $siteRef, string $fqdn, ?string $baseUrl = null): void
    {
        $this->published[] = ['fqdn' => $fqdn, 'baseUrl' => $baseUrl];
    }
}
