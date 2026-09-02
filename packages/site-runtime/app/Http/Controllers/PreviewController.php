<?php

namespace App\Http\Controllers;

use App\Rendering\RenderContext;
use App\Rendering\SiteConfig;
use App\Rendering\UrlContext;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webparaguay\Schema\SchemaValidator;

/**
 * Rutas de desarrollo: renderizan un sitio desde un archivo JSON.
 * Sin base de datos. El JSON se valida contra el esquema antes de pintar.
 *
 *   /preview   -> resources/schema/example-site.json  (plantilla institucional)
 *   /variants  -> resources/schema/variants-gallery.json  (regresión visual)
 */
class PreviewController extends Controller
{
    public function __construct(private SchemaValidator $validator) {}

    public function render(string $fixture, string $basePath, ?string $slug = null)
    {
        $path = $fixture === 'variants'
            ? (string) config('site-runtime.variants_site_path')
            : (string) config('site-runtime.preview_site_path');

        abort_unless(is_file($path), 404, 'No hay sitio configurado.');

        $raw = json_decode((string) file_get_contents($path), true);

        $errors = $this->validator->errors($raw);
        abort_if($errors !== [], 422, "El sitio no valida:\n - ".implode("\n - ", $errors));

        $site = SiteConfig::fromArray($raw);
        $page = $slug === null ? $site->homePage() : $site->pageBySlug($slug);

        if ($page === null) {
            throw new NotFoundHttpException("No existe la página «{$slug}».");
        }

        $url = new UrlContext($basePath, $page->slug(), $page->isHome());
        $ctx = new RenderContext($site, $page, $url);

        return response()
            ->view('site.page', ['ctx' => $ctx])
            ->header('X-Robots-Tag', 'noindex');
    }
}
