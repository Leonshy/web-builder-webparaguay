<?php

namespace App\Publishing;

use App\Models\Site;

/**
 * Enlace de acceso directo al CMS de la instancia publicada. El builder firma
 * un token corto (HMAC con el secreto compartido con la instancia); la
 * instancia lo valida y abre la sesión del dueño. Así el cliente no maneja dos
 * juegos de credenciales.
 */
final class CmsSso
{
    private const TTL_SECONDS = 120;

    public static function url(Site $site): string
    {
        $payload = json_encode([
            'email' => $site->cms_email,
            'name' => $site->project->user->name,
            'exp' => time() + self::TTL_SECONDS,
        ], JSON_THROW_ON_ERROR);

        $token = self::b64($payload).'.'.self::b64(self::sign($payload));

        return 'https://'.$site->live_fqdn.'/cms/sso?t='.$token;
    }

    private static function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, (string) config('generation.site_runtime_token'), true);
    }

    private static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
