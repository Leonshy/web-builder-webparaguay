<?php

namespace Tests\Feature;

use App\Cms\SiteAssembler;
use App\Models\Cms\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Webparaguay\Schema\Schema;

class PublishedSiteTest extends TestCase
{
    use RefreshDatabase;

    private function publishedSite(): Site
    {
        $doc = json_decode((string) file_get_contents(Schema::examplePath()), true);
        $site = Site::create([
            'name' => 'Cyber ya',
            'template' => $doc['template'],
            'theme' => $doc['theme'],
            'settings' => $doc['settings'],
        ]);
        (new SiteAssembler())->importInto($site, $doc);
        $site->update(['published_domain' => 'localhost', 'published_at' => now()]);

        return $site;
    }

    public function test_la_home_del_sitio_publicado_se_sirve_en_la_raiz(): void
    {
        $this->publishedSite();

        $this->get('/')
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_root_sin_sitio_publicado_da_404(): void
    {
        $this->get('/')->assertNotFound();
    }

    public function test_no_tapa_el_cms(): void
    {
        $this->publishedSite();

        $this->get('/cms')->assertOk();
    }
}
