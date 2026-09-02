<?php

namespace App\Rendering;

final class SitePage
{
    /** @param array<string,mixed> $raw */
    public function __construct(private array $raw, private int $declarationIndex) {}

    public function declarationIndex(): int
    {
        return $this->declarationIndex;
    }

    public function slug(): string
    {
        return $this->raw['slug'] ?? 'pagina-'.$this->declarationIndex;
    }

    public function title(): string
    {
        return $this->raw['title'] ?? '';
    }

    public function isHome(): bool
    {
        return (bool) ($this->raw['is_home'] ?? false);
    }

    public function showInNav(): bool
    {
        return (bool) ($this->raw['show_in_nav'] ?? true);
    }

    public function order(): int
    {
        return (int) ($this->raw['order'] ?? $this->declarationIndex);
    }

    /** @return array<string,mixed> */
    public function seo(): array
    {
        return $this->raw['seo'] ?? [];
    }

    /**
     * Secciones activas, ordenadas por `order`.
     *
     * @return array<int,SiteSection>
     */
    public function sections(): array
    {
        $sections = [];
        foreach ($this->raw['sections'] ?? [] as $index => $section) {
            if (($section['is_active'] ?? true) === false) {
                continue;
            }
            $sections[] = new SiteSection($section, $index);
        }

        usort($sections, fn (SiteSection $a, SiteSection $b) => [$a->order(), $a->declarationIndex()] <=> [$b->order(), $b->declarationIndex()]);

        return $sections;
    }
}
