<?php

namespace App\Rendering;

/**
 * Parsea URLs de video. Proveedores soportados: YouTube y Vimeo.
 *
 * Si la URL no parsea, `parse()` devuelve null y la sección `video` no se
 * renderiza (el despachador lo reporta en modo debug). No se inventa un embed.
 */
final class VideoEmbed
{
    /**
     * @return array{provider:string,id:string,embed_url:string,watch_url:string,thumbnail:?string}|null
     */
    public static function parse(?string $url): ?array
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) {
            return self::youtube($url, $host);
        }

        if (str_contains($host, 'vimeo.com')) {
            return self::vimeo($url);
        }

        return null;
    }

    /** @return array{provider:string,id:string,embed_url:string,watch_url:string,thumbnail:?string}|null */
    private static function youtube(string $url, string $host): ?array
    {
        $id = null;

        if (str_contains($host, 'youtu.be')) {
            $id = trim((string) parse_url($url, PHP_URL_PATH), '/');
        } else {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $id = $query['v'] ?? null;

            if ($id === null && preg_match('#/(embed|shorts)/([A-Za-z0-9_-]{6,})#', $url, $m)) {
                $id = $m[2];
            }
        }

        if (! $id || ! preg_match('/^[A-Za-z0-9_-]{6,}$/', $id)) {
            return null;
        }

        return [
            'provider' => 'youtube',
            'id' => $id,
            'embed_url' => "https://www.youtube-nocookie.com/embed/{$id}",
            'watch_url' => "https://www.youtube.com/watch?v={$id}",
            'thumbnail' => "https://i.ytimg.com/vi/{$id}/hqdefault.jpg",
        ];
    }

    /** @return array{provider:string,id:string,embed_url:string,watch_url:string,thumbnail:?string}|null */
    private static function vimeo(string $url): ?array
    {
        if (! preg_match('#vimeo\.com/(?:video/)?(\d{6,})#', $url, $m)) {
            return null;
        }

        $id = $m[1];

        return [
            'provider' => 'vimeo',
            'id' => $id,
            'embed_url' => "https://player.vimeo.com/video/{$id}",
            'watch_url' => "https://vimeo.com/{$id}",
            'thumbnail' => null,
        ];
    }
}
