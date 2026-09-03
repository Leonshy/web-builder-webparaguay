<?php

namespace App\Http\Controllers\Internal;

use App\Cms\SiteAssembler;
use App\Http\Controllers\Controller;
use App\Models\Cms\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webparaguay\Schema\SchemaValidator;

/**
 * API interna: apps/builder entrega el documento JSON ya generado y validado;
 * acá se importa como sitio editable y se devuelve un enlace de preview.
 *
 * Autenticada por token compartido (SITE_RUNTIME_INTERNAL_TOKEN). El día que
 * la publicación pase por otra vía, se reescribe esto, no el builder.
 */
class CreateSiteController extends Controller
{
    public function __construct(
        private SchemaValidator $validator,
        private SiteAssembler $assembler,
    ) {}

    public function __invoke(Request $request)
    {
        $expected = (string) config('site-runtime.internal_token');
        abort_if($expected === '' || ! hash_equals($expected, (string) $request->bearerToken()), 401);

        $data = $request->validate([
            'builder_project_ref' => 'required|string|max:120',
            'name' => 'required|string|max:120',
            'document' => 'required|array',
            'owner_email' => 'nullable|email|max:190',
            'owner_password' => 'nullable|string|min:8|max:120',
            'owner_name' => 'nullable|string|max:120',
        ]);

        $errors = $this->validator->errors($data['document']);
        abort_if($errors !== [], 422, implode(' | ', $errors));

        $site = DB::transaction(function () use ($data) {
            // Dueño del CMS de esta instancia. El builder manda las credenciales
            // al publicar; acá se crea/actualiza el usuario.
            if (! empty($data['owner_email'])) {
                $attrs = ['name' => $data['owner_name'] ?? 'Dueño del sitio'];
                if (! empty($data['owner_password'])) {
                    $attrs['password'] = $data['owner_password'];
                }
                User::updateOrCreate(['email' => $data['owner_email']], $attrs);
            }

            $site = Site::updateOrCreate(
                ['builder_project_ref' => $data['builder_project_ref']],
                ['name' => $data['name'], 'theme' => [], 'settings' => []],
            );

            $this->assembler->importInto($site, $data['document']);
            $site->previewTokens()->create(['label' => 'Generado', 'expires_at' => now()->addDays(30)]);

            return $site;
        });

        $token = $site->previewTokens()->latest('id')->first();

        return response()->json([
            'site_ref' => (string) $site->id,
            'preview_url' => url('/s/'.$token->token),
        ], 201);
    }
}
