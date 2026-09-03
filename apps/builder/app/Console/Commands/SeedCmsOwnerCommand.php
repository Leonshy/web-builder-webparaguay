<?php

namespace App\Console\Commands;

use App\Generation\SiteRuntimeClient;
use App\Models\Project;
use App\Publishing\CmsCredentials;
use Illuminate\Console\Command;

/**
 * (Re)crea el usuario dueño del CMS en la instancia publicada y muestra sus
 * credenciales. Útil para sitios que se publicaron antes de que el CMS tuviera
 * login, o para reenviar el alta si la instancia se recreó.
 *
 *   php artisan builder:seed-cms-owner {projectId}
 */
class SeedCmsOwnerCommand extends Command
{
    protected $signature = 'builder:seed-cms-owner {project : id del proyecto}';

    protected $description = 'Crea el dueño del CMS en la instancia publicada';

    public function handle(SiteRuntimeClient $runtime): int
    {
        $project = Project::findOrFail($this->argument('project'));
        $site = $project->site;

        if (! $site || ! $site->live_fqdn || ! $site->document) {
            $this->components->error('El proyecto no tiene un sitio publicado con documento.');

            return self::FAILURE;
        }

        $cms = CmsCredentials::ensure($site);

        try {
            $runtime->createSite(
                $project, $site->name, $site->document, 'https://'.$site->live_fqdn,
                $cms['email'], $cms['password'], $cms['name'],
            );
        } catch (\Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("CMS: https://{$site->live_fqdn}/cms");
        $this->components->twoColumnDetail('Usuario', $cms['email']);
        $this->components->twoColumnDetail('Contraseña', $cms['password']);

        return self::SUCCESS;
    }
}
