<?php

namespace App\Publishing;

use App\Models\Site;
use Illuminate\Support\Str;

/**
 * Credenciales del dueño para el CMS de la instancia publicada. Se generan una
 * sola vez por sitio y se guardan para mostrárselas al cliente en la pantalla
 * de publicación. La sesión del CMS vive en la instancia, no acá.
 */
final class CmsCredentials
{
    /** @return array{email:string,password:string,name:string} */
    public static function ensure(Site $site): array
    {
        $user = $site->project->user;

        if (! $site->cms_email || ! $site->cms_password) {
            $site->forceFill([
                'cms_email' => $user->email,
                'cms_password' => Str::password(14, symbols: false),
            ])->save();
        }

        return [
            'email' => $site->cms_email,
            'password' => $site->cms_password,
            'name' => $user->name,
        ];
    }
}
