<?php

namespace App\Ai;

/**
 * Precio de los modelos de terceros, en USD por millón de tokens.
 * Se mide en tokens; el cliente nunca ve un token (se traduce a créditos).
 *
 * Fuente: tarifas públicas de Anthropic (revisar al cambiar de modelo).
 */
final class ModelPricing
{
    /** @var array<string, array{input: float, output: float}> USD / 1M tokens */
    private const TABLE = [
        'claude-opus-5' => ['input' => 15.0, 'output' => 75.0],
        'claude-sonnet-5' => ['input' => 3.0, 'output' => 15.0],
        'claude-haiku-4-5' => ['input' => 1.0, 'output' => 5.0],
        'claude-fable-5-1' => ['input' => 3.0, 'output' => 15.0],
    ];

    private const FALLBACK = ['input' => 3.0, 'output' => 15.0];

    /** Costo en millonésimas de USD (microUSD), entero, para guardar sin float. */
    public static function costMicroUsd(string $model, int $inputTokens, int $outputTokens): int
    {
        $rate = self::TABLE[$model] ?? self::FALLBACK;

        $usd = ($inputTokens / 1_000_000) * $rate['input']
            + ($outputTokens / 1_000_000) * $rate['output'];

        return (int) round($usd * 1_000_000);
    }

    public static function isKnown(string $model): bool
    {
        return isset(self::TABLE[$model]);
    }
}
