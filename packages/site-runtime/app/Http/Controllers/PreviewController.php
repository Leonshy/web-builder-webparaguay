<?php

namespace App\Http\Controllers;

use App\Rendering\RenderContext;
use App\Rendering\SchemaValidator;
use App\Rendering\SiteConfig;
use App\Rendering\UrlContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Ruta de desarrollo: renderiza el sitio de ejemplo desde un archivo JSON.
 * Sin base de datos. El JSON se valida contra el esquema antes de pintar.
 */
class PreviewController extends Controller
{
    private const BASE_PATH = '/preview';

    public function __invoke(Request $request, SchemaValidator $validator, ?string $slug = null)
    {
        $path = (string) config('site-runtime.preview_site_path');

        abort_unless(is_file($path), 404, 'No hay sitio de ejemplo configurado.');

        $raw = json_decode((string) file_get_contents($path), true);

        $errors = $validator->errors($raw);
        abort_if($errors !== [], 422, "El sitio de ejemplo no valida:\n - ".implode("\n - ", $errors));

        $site = SiteConfig::fromArray($raw);

        $page = $slug === null
            ? $site->homePage()
            : $site->pageBySlug($slug);

        if ($page === null) {
            throw new NotFoundHttpException("No existe la página «{$slug}».");
        }

        $url = new UrlContext(self::BASE_PATH, $page->slug(), $page->isHome());
        $ctx = new RenderContext($site, $page, $url);

        return response()
            ->view('site.page', ['ctx' => $ctx])
            ->header('X-Robots-Tag', 'noindex');
    }
}
