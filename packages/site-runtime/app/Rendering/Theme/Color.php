<?php

namespace App\Rendering\Theme;

/**
 * Utilidades de color en el espacio sRGB / HSL.
 *
 * Se usan para DERIVAR los tokens que el agente de Marca no provee
 * (primary_dark, surface, border, ...) y para garantizar contraste AA.
 * El agente solo acierta cuatro colores; el resto es determinístico.
 */
final class Color
{
    /** @var array{0:int,1:int,2:int} */
    public array $rgb;

    private function __construct(int $r, int $g, int $b)
    {
        $this->rgb = [
            max(0, min(255, $r)),
            max(0, min(255, $g)),
            max(0, min(255, $b)),
        ];
    }

    public static function fromHex(string $hex): self
    {
        $hex = ltrim($hex, '#');

        return new self(
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        );
    }

    public function toHex(): string
    {
        return sprintf('#%02x%02x%02x', $this->rgb[0], $this->rgb[1], $this->rgb[2]);
    }

    /** Luminancia relativa WCAG (0 = negro, 1 = blanco). */
    public function luminance(): float
    {
        $channels = array_map(function (int $c): float {
            $s = $c / 255;

            return $s <= 0.03928 ? $s / 12.92 : (($s + 0.055) / 1.055) ** 2.4;
        }, $this->rgb);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    public function contrastRatio(self $other): float
    {
        $a = $this->luminance();
        $b = $other->luminance();
        [$light, $dark] = $a >= $b ? [$a, $b] : [$b, $a];

        return ($light + 0.05) / ($dark + 0.05);
    }

    public function isDark(): bool
    {
        return $this->luminance() < 0.5;
    }

    /** Mezcla lineal hacia otro color. $amount 0..1. */
    public function mix(self $other, float $amount): self
    {
        $amount = max(0.0, min(1.0, $amount));

        return new self(
            (int) round($this->rgb[0] + ($other->rgb[0] - $this->rgb[0]) * $amount),
            (int) round($this->rgb[1] + ($other->rgb[1] - $this->rgb[1]) * $amount),
            (int) round($this->rgb[2] + ($other->rgb[2] - $this->rgb[2]) * $amount),
        );
    }

    public function darken(float $amount): self
    {
        return $this->mix(self::fromHex('#000000'), $amount);
    }

    public function lighten(float $amount): self
    {
        return $this->mix(self::fromHex('#ffffff'), $amount);
    }

    /** Devuelve blanco o negro, el que mejor contraste da sobre este color. */
    public function readableInk(): self
    {
        $white = self::fromHex('#ffffff');
        $black = self::fromHex('#111111');

        return $this->contrastRatio($white) >= $this->contrastRatio($black) ? $white : $black;
    }

    /**
     * Ajusta la luminosidad de $this hasta alcanzar $ratio contra $bg.
     * Un sitio ilegible es peor que un sitio genérico.
     */
    public function ensureContrast(self $bg, float $ratio = 4.5): self
    {
        if ($this->contrastRatio($bg) >= $ratio) {
            return $this;
        }

        $target = $bg->isDark() ? self::fromHex('#ffffff') : self::fromHex('#000000');
        $current = $this;

        for ($step = 1; $step <= 20; $step++) {
            $current = $current->mix($target, 0.05);
            if ($current->contrastRatio($bg) >= $ratio) {
                return $current;
            }
        }

        return $target;
    }
}
