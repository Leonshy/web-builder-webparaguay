<?php

namespace App\Cms;

/**
 * Valida el token de acceso directo que firma el builder (HMAC con el secreto
 * compartido). Devuelve el payload si el token es válido y no venció.
 */
final class CmsSso
{
    /** @return array{email:string,name:string,exp:int}|null */
    public static function verify(string $token): ?array
    {
        $secret = (string) config('site-runtime.internal_token');
        if ($secret === '' || ! str_contains($token, '.')) {
            return null;
        }

        [$payloadB64, $sigB64] = explode('.', $token, 2);
        $payload = self::unb64($payloadB64);
        $expected = hash_hmac('sha256', $payload, $secret, true);

        if (! hash_equals($expected, self::unb64($sigB64))) {
            return null;
        }

        $data = json_decode($payload, true);
        if (! is_array($data) || empty($data['email']) || ($data['exp'] ?? 0) < time()) {
            return null;
        }

        return $data;
    }

    private static function unb64(string $b64): string
    {
        return base64_decode(strtr($b64, '-_', '+/'), true) ?: '';
    }
}
