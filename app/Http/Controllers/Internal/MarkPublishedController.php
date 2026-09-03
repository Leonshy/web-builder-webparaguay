<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Cms\Site;
use Illuminate\Http\Request;

/**
 * API interna: apps/builder informa que un sitio quedó publicado en un dominio.
 * site-runtime lo usa para el canonical y el sitemap.
 */
class MarkPublishedController extends Controller
{
    public function __invoke(Request $request, Site $site)
    {
        $expected = (string) config('site-runtime.internal_token');
        abort_if($expected === '' || ! hash_equals($expected, (string) $request->bearerToken()), 401);

        $data = $request->validate(['fqdn' => 'required|string|max:190']);

        $site->update([
            'published_domain' => $data['fqdn'],
            'published_at' => $site->published_at ?? now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
