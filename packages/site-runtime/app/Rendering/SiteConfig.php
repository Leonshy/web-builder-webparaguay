<?php

namespace App\Rendering;

use JsonException;

/**
 * Vista tipada del JSON de un sitio ya validado.
 *
 * No valida (de eso se ocupa SchemaValidator). Aplica únicamente los
 * defaults del contrato que el renderer necesita para no repetir `?? true`
 * en cada componente: is_active, order.
 */
final class SiteConfig
{
    /** @param array<string,mixed> $raw */
    public function __construct(private array $raw) {}

    public static function fromFile(string $path): self
    {
        try {
            $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new \RuntimeException("El archivo del sitio no es JSON válido: {$path}", previous: $e);
        }

        return new self($decoded);
    }

    /** @param array<string,mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self($raw);
    }

    /** @return array<string,mixed> */
    public function raw(): array
    {
        return $this->raw;
    }

    public function template(): string
    {
        return $this->raw['template'] ?? 'landing';
    }

    /** @return array<string,mixed> */
    public function theme(): array
    {
        return $this->raw['theme'] ?? [];
    }

    /** @return array<string,mixed> */
    public function settings(): array
    {
        return $this->raw['settings'] ?? [];
    }

    public function businessName(): string
    {
        return $this->settings()['business_name'] ?? '';
    }

    /** @return array<string,mixed> */
    public function layout(): array
    {
        return $this->raw['layout'] ?? [];
    }

    /**
     * Páginas activas, ordenadas por `order` y luego por aparición.
     *
     * @return array<int,SitePage>
     */
    public function pages(): array
    {
        $pages = [];
        foreach ($this->raw['pages'] ?? [] as $index => $page) {
            if (($page['is_active'] ?? true) === false) {
                continue;
            }
            $pages[] = new SitePage($page, $index);
        }

        usort($pages, fn (SitePage $a, SitePage $b) => [$a->order(), $a->declarationIndex()] <=> [$b->order(), $b->declarationIndex()]);

        return $pages;
    }

    public function homePage(): ?SitePage
    {
        $pages = $this->pages();
        foreach ($pages as $page) {
            if ($page->isHome()) {
                return $page;
            }
        }

        return $pages[0] ?? null;
    }

    public function pageBySlug(string $slug): ?SitePage
    {
        foreach ($this->pages() as $page) {
            if ($page->slug() === $slug) {
                return $page;
            }
        }

        return null;
    }
}
