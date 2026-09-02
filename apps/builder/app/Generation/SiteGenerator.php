<?php

namespace App\Generation;

use App\Models\Project;
use RuntimeException;
use Webparaguay\Schema\SchemaValidator;

/**
 * Orquesta la generación: brief → documento → validación (con 1 reintento) →
 * handoff a site-runtime → estado del proyecto.
 *
 * Si el documento no valida ni después del reintento, el proyecto queda en
 * "needs_fix": NUNCA se publica un sitio que no valida (regla del CLAUDE.md).
 */
final class SiteGenerator
{
    public function __construct(
        private Generator $generator,
        private SchemaValidator $validator,
        private SiteRuntimeClient $runtime,
    ) {}

    /**
     * @return array{preview_url:string, site_ref:string}
     */
    public function run(Project $project): array
    {
        $draft = $project->interviewDraft;

        if (! $draft || ! $draft->allConfirmed()) {
            throw new RuntimeException('La entrevista no está completa: faltan etapas por confirmar.');
        }

        $draft->update(['stage' => 'generating', 'last_error' => null]);

        $brief = Brief::fromDraft($draft);
        $org = $project->organization;
        $name = $brief->settings['business_name'] ?? $project->name;

        $document = $this->generator->generate($brief, $org, $project);
        $errors = $this->validator->errors($document);

        if ($errors !== []) {
            $document = $this->generator->repair($brief, $document, $errors, $org, $project);
            $errors = $this->validator->errors($document);
        }

        if ($errors !== []) {
            $draft->update(['stage' => 'needs_fix', 'last_error' => implode(' | ', array_slice($errors, 0, 3))]);
            throw new GenerationFailed($errors);
        }

        $result = $this->runtime->createSite($project, $name, $document);

        $project->site()->updateOrCreate([], [
            'name' => $name,
            'runtime_site_ref' => $result['site_ref'],
            'preview_url' => $result['preview_url'],
            'document' => $document,
        ]);
        $project->update(['status' => 'generated']);
        $draft->update(['stage' => 'done']);

        return $result;
    }
}
