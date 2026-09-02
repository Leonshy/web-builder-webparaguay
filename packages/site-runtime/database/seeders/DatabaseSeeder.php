<?php

namespace Database\Seeders;

use App\Cms\SiteAssembler;
use App\Models\Cms\Site;
use Illuminate\Database\Seeder;
use Webparaguay\Schema\Schema;
use Webparaguay\Schema\SchemaValidator;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $doc = json_decode((string) file_get_contents(Schema::examplePath()), true);

        (new SchemaValidator())->assertValid($doc);

        $site = Site::firstOrCreate(
            ['name' => $doc['settings']['business_name'] ?? 'Sitio de ejemplo'],
            ['template' => $doc['template'] ?? 'landing', 'theme' => $doc['theme'], 'settings' => $doc['settings']],
        );

        (new SiteAssembler())->importInto($site, $doc);

        $this->command->info("Sitio «{$site->name}» sembrado: ".$site->pages()->count().' páginas, '
            .$site->pages()->withCount('sections')->get()->sum('sections_count').' secciones.');
    }
}
