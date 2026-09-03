<?php

namespace App\Http\Controllers\Cms;

use App\Cms\SiteValidator;
use App\Http\Controllers\Controller;
use App\Models\Cms\Site;
use Illuminate\Http\Request;

class CmsController extends Controller
{
    public function __construct(private SiteValidator $validator) {}

    public function index()
    {
        return view('cms.index', ['sites' => Site::withCount('pages')->latest()->get()]);
    }

    public function show(Site $site)
    {
        $site->load('pages.sections');

        return view('cms.site', [
            'site' => $site,
            'schemaErrors' => $this->validator->errorsFor($site),
        ]);
    }

    public function update(Request $request, Site $site)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'template' => 'required|in:landing,institucional,catalogo,ecommerce',
        ]);

        foreach (['theme', 'settings', 'layout'] as $key) {
            $raw = trim((string) $request->input("{$key}__json", ''));
            $decoded = $raw === '' ? null : json_decode($raw, true);

            if ($raw !== '' && json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(["{$key}" => "El JSON de «{$key}» está mal formado: ".json_last_error_msg()]);
            }
            $data[$key] = $decoded;
        }

        $this->validator->persistOrRollback($site, fn () => $site->update($data));

        return redirect()->route('cms.site', $site)->with('ok', 'Sitio actualizado.');
    }

    public function storePage(Request $request, Site $site)
    {
        $data = $request->validate([
            'slug' => 'required|string|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|max:60|unique:pages,slug,NULL,id,site_id,'.$site->id,
            'title' => 'required|string|max:80',
        ]);

        $page = $this->validator->persistOrRollback($site, function () use ($site, $data) {
            $isHome = $site->pages()->count() === 0;

            $page = $site->pages()->create([
                ...$data,
                'position' => ($site->pages()->max('position') ?? -1) + 1,
                'is_home' => $isHome,
            ]);

            // Una página necesita al menos una sección para validar.
            $page->sections()->create($isHome
                ? ['type' => 'hero', 'variant' => 'minimal', 'position' => 0, 'content' => ['headline' => $page->title]]
                : ['type' => 'page_header', 'variant' => 'simple', 'position' => 0, 'content' => ['heading' => $page->title]]);

            return $page;
        });

        return redirect()->route('cms.page', $page)->with('ok', 'Página creada.');
    }

    public function storePreviewToken(Request $request, Site $site)
    {
        $token = $site->previewTokens()->create([
            'label' => $request->string('label')->toString() ?: null,
            'expires_at' => now()->addDays(30),
        ]);

        return redirect()->route('cms.site', $site)
            ->with('ok', 'Enlace de preview creado: '.route('cms.preview', $token->token));
    }
}
