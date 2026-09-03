<?php

namespace App\Http\Controllers\Cms;

use App\Cms\SiteAssembler;
use App\Http\Controllers\Controller;
use App\Models\Cms\PreviewToken;
use App\Rendering\RenderContext;
use App\Rendering\SiteConfig;
use App\Rendering\UrlContext;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Preview con token: enlace no indexable, compartible.
 * Renderiza el sitio directo desde la base (SiteAssembler), sin publicar.
 */
class CmsPreviewController extends Controller
{
    public function __construct(private SiteAssembler $assembler) {}

    public function __invoke(string $token, ?string $slug = null)
    {
        $previewToken = PreviewToken::where('token', $token)->firstOr(fn () => throw new NotFoundHttpException());
        abort_if($previewToken->isExpired(), 410, 'El enlace de preview venció.');

        $previewToken->forceFill(['last_viewed_at' => now()])->save();

        $site = $previewToken->site->load('pages.sections');
        $doc = $this->assembler->toArray($site);

        $config = SiteConfig::fromArray($doc);
        $base = '/s/'.$token;
        $page = $slug === null ? $config->homePage() : $config->pageBySlug($slug);

        if ($page === null) {
            throw new NotFoundHttpException("No existe la página «{$slug}».");
        }

        $url = new UrlContext($base, $page->slug(), $page->isHome());
        $ctx = new RenderContext($config, $page, $url);

        return response()
            ->view('site.page', ['ctx' => $ctx])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
