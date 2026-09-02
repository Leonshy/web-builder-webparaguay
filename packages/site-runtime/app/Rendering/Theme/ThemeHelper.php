<?php

namespace App\Rendering\Theme;

/**
 * Convierte theme.colors / theme.typography / theme.shape del JSON del sitio
 * en variables CSS para :root.
 *
 * Reglas:
 *  - El agente solo produce primary, background, text (accent opcional).
 *  - Todo lo demás se deriva acá, de forma determinística.
 *  - Se garantiza contraste AA para text/background y dark_text/dark_bg.
 *  - Los componentes Blade consumen SOLO estas variables, nunca literales.
 */
final class ThemeHelper
{
    /** @param array<string,mixed> $theme */
    public function __construct(private array $theme) {}

    /** @return array<string,string> mapa nombre-de-variable => valor */
    public function cssVariables(): array
    {
        return [...$this->colorVariables(), ...$this->typographyVariables(), ...$this->shapeVariables()];
    }

    /** Bloque `:root { ... }` listo para incrustar en el <head>. */
    public function rootBlock(): string
    {
        $lines = [];
        foreach ($this->cssVariables() as $name => $value) {
            $lines[] = "  {$name}: {$value};";
        }

        return ":root{\n".implode("\n", $lines)."\n}";
    }

    public function googleFontsUrl(): string
    {
        $pairing = FontPairings::resolve($this->theme['typography']['pairing'] ?? null);

        return 'https://fonts.googleapis.com/css2?'.$pairing['google'].'&display=swap';
    }

    /** @return array<string,string> */
    private function colorVariables(): array
    {
        $colors = $this->theme['colors'] ?? [];

        $primary = Color::fromHex($colors['primary'] ?? '#2563eb');
        $background = Color::fromHex($colors['background'] ?? '#ffffff');
        $text = Color::fromHex($colors['text'] ?? '#111111')->ensureContrast($background, 4.5);
        $accent = isset($colors['accent'])
            ? Color::fromHex($colors['accent'])
            : $primary->mix($background->isDark() ? Color::fromHex('#ffffff') : Color::fromHex('#000000'), 0.15);

        $primaryDark = isset($colors['primary_dark'])
            ? Color::fromHex($colors['primary_dark'])
            : $primary->darken(0.18);

        $surface = isset($colors['surface'])
            ? Color::fromHex($colors['surface'])
            : ($background->isDark() ? $background->lighten(0.06) : $background->darken(0.04));

        $muted = isset($colors['text_muted'])
            ? Color::fromHex($colors['text_muted'])
            : $text->mix($background, 0.38)->ensureContrast($background, 4.5);

        $border = isset($colors['border'])
            ? Color::fromHex($colors['border'])
            : $text->mix($background, 0.82);

        $darkBg = isset($colors['dark_bg'])
            ? Color::fromHex($colors['dark_bg'])
            : ($primary->isDark() ? $primary->darken(0.35) : Color::fromHex('#141414'));

        $darkText = isset($colors['dark_text'])
            ? Color::fromHex($colors['dark_text'])->ensureContrast($darkBg, 4.5)
            : Color::fromHex('#ffffff')->ensureContrast($darkBg, 4.5);

        $onPrimary = $primary->readableInk();
        $onAccent = $accent->readableInk();

        return [
            '--wp-primary' => $primary->toHex(),
            '--wp-primary-dark' => $primaryDark->toHex(),
            '--wp-on-primary' => $onPrimary->toHex(),
            '--wp-accent' => $accent->toHex(),
            '--wp-on-accent' => $onAccent->toHex(),
            '--wp-bg' => $background->toHex(),
            '--wp-surface' => $surface->toHex(),
            '--wp-text' => $text->toHex(),
            '--wp-text-muted' => $muted->toHex(),
            '--wp-border' => $border->toHex(),
            '--wp-dark-bg' => $darkBg->toHex(),
            '--wp-dark-surface' => $darkBg->lighten(0.08)->toHex(),
            '--wp-dark-text' => $darkText->toHex(),
            '--wp-dark-text-muted' => $darkText->mix($darkBg, 0.35)->toHex(),
            '--wp-dark-border' => $darkText->mix($darkBg, 0.78)->toHex(),
        ];
    }

    /** @return array<string,string> */
    private function typographyVariables(): array
    {
        $typo = $this->theme['typography'] ?? [];
        $pairing = FontPairings::resolve($typo['pairing'] ?? null);

        $scale = $typo['scale'] ?? 'normal';
        $ratio = match ($scale) {
            'compact' => 1.18,
            'spacious' => 1.30,
            default => 1.24,
        };
        $base = match ($scale) {
            'compact' => 1.0,
            'spacious' => 1.075,
            default => 1.0,
        };

        $step = fn (int $n): string => round($base * ($ratio ** $n), 3).'rem';

        return [
            '--wp-font-heading' => $pairing['heading_stack'],
            '--wp-font-body' => $pairing['body_stack'],
            '--wp-weight-heading' => (string) ($typo['heading_weight'] ?? 700),
            '--wp-weight-body' => (string) ($typo['body_weight'] ?? 400),
            '--wp-text-xs' => $step(-2),
            '--wp-text-sm' => $step(-1),
            '--wp-text-base' => $step(0),
            '--wp-text-lg' => $step(1),
            '--wp-text-xl' => $step(2),
            '--wp-text-2xl' => $step(3),
            '--wp-text-3xl' => $step(4),
            '--wp-text-4xl' => $step(5),
            '--wp-text-5xl' => $step(6),
        ];
    }

    /** @return array<string,string> */
    private function shapeVariables(): array
    {
        $shape = $this->theme['shape'] ?? [];

        $radius = match ($shape['radius'] ?? 'md') {
            'none' => '0px',
            'sm' => '0.25rem',
            'lg' => '1rem',
            'full' => '1.75rem',
            default => '0.625rem',
        };

        $shadow = match ($shape['shadow'] ?? 'soft') {
            'none' => 'none',
            'strong' => '0 20px 45px -12px rgba(15, 23, 42, 0.32)',
            default => '0 12px 30px -14px rgba(15, 23, 42, 0.22)',
        };

        [$sectionY, $gap] = match ($shape['density'] ?? 'normal') {
            'compact' => ['3.5rem', '1.25rem'],
            'airy' => ['7rem', '2.25rem'],
            default => ['5rem', '1.75rem'],
        };

        return [
            '--wp-radius' => $radius,
            '--wp-radius-sm' => 'calc('.$radius.' * 0.55)',
            '--wp-shadow' => $shadow,
            '--wp-section-y' => $sectionY,
            '--wp-gap' => $gap,
        ];
    }
}
