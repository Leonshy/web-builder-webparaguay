<?php

namespace App\Publishing;

use App\Generation\SiteRuntimeClient;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Throwable;
use Webparaguay\Provisioning\InstanceConfigurator;

/**
 * Siembra el sitio generado en la instancia ya publicada (Plesk): entrega el
 * documento y las credenciales del CMS, marca el sitio en línea y cierra las
 * tareas de back-office.
 *
 * Es idempotente y tolerante: si la instancia todavía no responde (el CMS aún
 * no se desplegó / no hay SSL), devuelve false y no toca el estado. Un job/cron
 * lo reintenta hasta que la instancia está lista.
 */
final class SeedInstance
{
    public function __construct(private SiteRuntimeClient $runtime) {}

    public function run(Project $project): bool
    {
        $site = $project->site;

        if (! $site || ! $site->live_fqdn || ! $site->document) {
            return false;
        }

        if (! $this->configureInstance($project, $site)) {
            return false;
        }

        $instanceUrl = 'https://'.$site->live_fqdn;
        $cms = CmsCredentials::ensure($site);

        try {
            $seeded = $this->runtime->createSite(
                $project, $site->name, $site->document, $instanceUrl,
                $cms['email'], $cms['password'], $cms['name'],
            );
            $this->runtime->markPublished($seeded['site_ref'], $site->live_fqdn, $instanceUrl);
        } catch (Throwable $e) {
            return false;
        }

        $this->finalize($project, $site, $seeded);

        return true;
    }

    /**
     * Aprovisiona la suscripción de Plesk (BD, document root, git al tag,
     * `.env`, SSL) una sola vez. Si Plesk no está configurado (dev), no hace
     * nada y sigue de largo.
     */
    private function configureInstance(Project $project, $site): bool
    {
        if ($site->instance_configured_at || ! config('publishing.plesk.api_key')) {
            return true;
        }

        try {
            app(InstanceConfigurator::class)->configure($site->live_fqdn);
            $site->update(['instance_configured_at' => now()]);

            return true;
        } catch (Throwable $e) {
            $project->backofficeTasks()->updateOrCreate(
                ['kind' => 'configure_instance', 'status' => 'open'],
                ['note' => "Aprovisionar {$site->live_fqdn} en Plesk: {$e->getMessage()}"],
            );

            return false;
        }
    }

    private function finalize(Project $project, $site, array $seeded): void
    {
        DB::transaction(function () use ($project, $site, $seeded) {
            $site->update([
                'runtime_site_ref' => $seeded['site_ref'],
                'published_domain' => $site->live_fqdn,
                'published_at' => $site->published_at ?? now(),
            ]);
            $project->payments()->where('status', 'pending')->update(['status' => 'paid', 'paid_at' => now()]);
            $project->backofficeTasks()
                ->whereIn('kind', ['seed_instance', 'confirm_order_payment', 'configure_instance'])
                ->where('status', 'open')
                ->update(['status' => 'done']);
            $project->update(['status' => 'published']);
        });
    }
}
