<?php

namespace App\Rendering;

use Illuminate\Support\Str;

final class SiteSection
{
    /** @param array<string,mixed> $raw */
    public function __construct(private array $raw, private int $declarationIndex) {}

    public function declarationIndex(): int
    {
        return $this->declarationIndex;
    }

    public function id(): string
    {
        return $this->raw['id'] ?? ('sec-'.$this->type().'-'.$this->declarationIndex);
    }

    public function type(): string
    {
        return $this->raw['type'] ?? '';
    }

    public function variant(): string
    {
        return $this->raw['variant'] ?? '';
    }

    public function order(): int
    {
        return (int) ($this->raw['order'] ?? $this->declarationIndex);
    }

    /** Ancla explícita del envelope, o null. La navegación la usa para saltos internos. */
    public function anchor(): ?string
    {
        $anchor = $this->raw['anchor'] ?? null;

        return $anchor ? Str::slug($anchor) : null;
    }

    /** Id usable como destino de ancla: la explícita o el id de sección. */
    public function domId(): string
    {
        return $this->anchor() ?? Str::slug($this->id());
    }

    public function label(): ?string
    {
        return $this->raw['label'] ?? null;
    }

    public function title(): ?string
    {
        return $this->raw['title'] ?? null;
    }

    public function subtitle(): ?string
    {
        return $this->raw['subtitle'] ?? null;
    }

    /** default | muted | dark | image */
    public function background(): string
    {
        return $this->raw['background'] ?? 'default';
    }

    public function isDark(): bool
    {
        return $this->background() === 'dark';
    }

    /** @return array<string,mixed>|null */
    public function backgroundImage(): ?array
    {
        return $this->raw['background_image'] ?? null;
    }

    /** @return array<string,mixed> */
    public function content(): array
    {
        return $this->raw['content'] ?? [];
    }

    /** Texto para el enlace de navegación a esta sección, o null si no debe aparecer. */
    public function navLabel(): ?string
    {
        if ($this->anchor() === null) {
            return null;
        }

        return $this->label() ?? $this->title();
    }
}
