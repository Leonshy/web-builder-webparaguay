<?php

namespace App\Ai;

use App\Models\AiUsage;
use App\Models\Organization;
use App\Models\Project;

/**
 * Registra TODA llamada a un modelo desde el día uno (regla 19 del CLAUDE.md):
 * cuenta, proyecto, acción, tokens de entrada y salida, modelo, costo, timestamp.
 *
 * Se llena aunque los créditos se vendan recién en la v1. Nunca se muestra un
 * token al cliente; el costo se traduce a créditos en la capa de presentación.
 */
final class AiUsageRecorder
{
    public function record(
        Organization $organization,
        ?Project $project,
        string $action,
        string $model,
        int $inputTokens,
        int $outputTokens,
    ): AiUsage {
        return AiUsage::create([
            'organization_id' => $organization->id,
            'project_id' => $project?->id,
            'action' => $action,
            'model' => $model,
            'input_tokens' => max(0, $inputTokens),
            'output_tokens' => max(0, $outputTokens),
            'cost_microusd' => ModelPricing::costMicroUsd($model, $inputTokens, $outputTokens),
            'occurred_at' => now(),
        ]);
    }

    /** Costo acumulado de una organización, en USD. */
    public function totalUsd(Organization $organization): float
    {
        return $organization->aiUsages()->sum('cost_microusd') / 1_000_000;
    }
}
