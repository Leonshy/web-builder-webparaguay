<?php

namespace App\Rendering;

use App\Rendering\Theme\ThemeHelper;

/**
 * Todo lo que un componente necesita para pintar una página, en un objeto.
 * Se pasa hacia abajo por el árbol de Blade como `:ctx`.
 */
final class RenderContext
{
    public readonly ThemeHelper $theme;

    public readonly ButtonResolver $buttons;

    public readonly HtmlSanitizer $sanitizer;

    /** @var array<int,array<string,mixed>> */
    public readonly array $navigation;

    public function __construct(
        public readonly SiteConfig $site,
        public readonly SitePage $page,
        public readonly UrlContext $url,
    ) {
        $this->theme = new ThemeHelper($site->theme());
        $this->buttons = new ButtonResolver($site->settings(), $url, $site);
        $this->sanitizer = new HtmlSanitizer();
        $this->navigation = Navigation::build($site, $page, $url);
    }

    /** @param array<string,mixed>|null $button */
    public function button(?array $button): ?array
    {
        return $this->buttons->resolve($button);
    }

    public function richtext(?string $html): string
    {
        return $this->sanitizer->clean($html);
    }

    /** @return array<string,mixed> */
    public function settings(): array
    {
        return $this->site->settings();
    }

    public function pageTitle(): string
    {
        $seo = $this->page->seo();

        return $seo['title'] ?? trim($this->page->title().' | '.$this->site->businessName(), ' |');
    }

    public function metaDescription(): ?string
    {
        return $this->page->seo()['description'] ?? ($this->site->settings()['tagline'] ?? null);
    }

    public function noindex(): bool
    {
        return (bool) ($this->page->seo()['noindex'] ?? false);
    }
}
