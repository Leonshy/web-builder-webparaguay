<?php

namespace App\Http\Controllers\Cms;

use App\Cms\SchemaForm;
use App\Cms\SiteValidator;
use App\Http\Controllers\Controller;
use App\Models\Cms\Page;
use Illuminate\Http\Request;

class CmsPageController extends Controller
{
    public function __construct(private SiteValidator $validator, private SchemaForm $form) {}

    public function show(Page $page)
    {
        $page->load('sections', 'site');

        return view('cms.page', [
            'page' => $page,
            'sectionTypes' => $this->form->sectionTypes(),
        ]);
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title' => 'required|string|max:80',
            'slug' => 'required|string|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|max:60',
        ]);

        $rawSeo = trim((string) $request->input('seo__json', ''));
        $seo = $rawSeo === '' ? null : json_decode($rawSeo, true);
        if ($rawSeo !== '' && json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['seo' => 'El JSON de SEO está mal formado.']);
        }
        $data['seo'] = $seo;

        $this->validator->persistOrRollback($page->site, function () use ($page, $data, $request) {
            $page->update([
                ...$data,
                'is_home' => $request->boolean('is_home'),
                'is_active' => $request->boolean('is_active'),
                'show_in_nav' => $request->boolean('show_in_nav'),
            ]);

            // is_home es exclusivo.
            if ($request->boolean('is_home')) {
                $page->site->pages()->whereKeyNot($page->id)->update(['is_home' => false]);
            }
        });

        return redirect()->route('cms.page', $page)->with('ok', 'Página actualizada.');
    }

    public function destroy(Page $page)
    {
        $site = $page->site;
        abort_if($site->pages()->count() <= 1, 422, 'Un sitio necesita al menos una página.');

        $this->validator->persistOrRollback($site, fn () => $page->delete());

        return redirect()->route('cms.site', $site)->with('ok', 'Página eliminada.');
    }
}
