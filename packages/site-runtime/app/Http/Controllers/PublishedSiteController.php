<?php

namespace App\Http\Controllers;

use App\Cms\SiteAssembler;
use App\Models\Cms\Site;
use App\Rendering\RenderContext;
use App\Rendering\SiteConfig;
use App\Rendering\UrlContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Sitio público publicado. Cada instancia de site-runtime sirve UN sitio en la
 * raíz de su dominio: la home en `/` y las demás páginas en `/{slug}`.
 * Se renderiza desde la base (SiteAssembler) igual que el preview.
 */
class PublishedSiteController extends Controller
{
    public function __construct(private SiteAssembler $assembler) {}

    public function __invoke(Request $request, ?string $slug = null)
    {
        $site = Site::whereNotNull('published_at')
            ->where(fn ($q) => $q->where('published_domain', $request->getHost())->orWhereNull('published_domain'))
            ->latest('published_at')
            ->firstOr(fn () => throw new NotFoundHttpException('No hay un sitio publicado en esta instancia.'));

        $site->load('pages.sections');
        $config = SiteConfig::fromArray($this->assembler->toArray($site));
        $page = $slug === null ? $config->homePage() : $config->pageBySlug($slug);

        if ($page === null) {
            throw new NotFoundHttpException("No existe la página «{$slug}».");
        }

        $url = new UrlContext('', $page->slug(), $page->isHome());
        $ctx = new RenderContext($config, $page, $url);

        return response()->view('site.page', ['ctx' => $ctx]);
    }
}
