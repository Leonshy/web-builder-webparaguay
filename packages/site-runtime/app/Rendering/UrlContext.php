<?php

namespace App\Rendering;

/**
 * Resuelve URLs internas del sitio.
 *
 * El renderer se sirve tanto en la raíz de un dominio publicado como bajo
 * un prefijo (/preview) en desarrollo. Todo enlace interno pasa por acá.
 */
final class UrlContext
{
    public function __construct(
        private string $basePath = '',
        private ?string $currentSlug = null,
        private bool $currentIsHome = false,
    ) {}

    private function base(): string
    {
        return rtrim($this->basePath, '/');
    }

    public function pageHref(SitePage $page): string
    {
        $base = $this->base();

        if ($page->isHome()) {
            return $base === '' ? '/' : $base;
        }

        return $base.'/'.$page->slug();
    }

    public function anchorHref(SitePage $page, string $anchor): string
    {
        $anchor = ltrim($anchor, '#');

        $samePage = $this->currentSlug !== null
            && ($page->slug() === $this->currentSlug || ($page->isHome() && $this->currentIsHome));

        return ($samePage ? '' : $this->pageHref($page)).'#'.$anchor;
    }

    public function homeAnchorHref(SiteConfig $site, string $anchor): string
    {
        $home = $site->homePage();

        return $home ? $this->anchorHref($home, $anchor) : '#'.ltrim($anchor, '#');
    }
}
