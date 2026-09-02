<?php

namespace App\Generation;

use App\Models\Organization;
use App\Models\Project;

/**
 * Un generador toma un brief y devuelve el DOCUMENTO JSON de un sitio.
 *
 * El documento se valida SIEMPRE contra site.schema.json después. Un
 * generador nunca escribe código: sólo produce el JSON de configuración.
 */
interface Generator
{
    /**
     * @return array<string,mixed> documento de sitio (sin validar todavía)
     */
    public function generate(Brief $brief, Organization $organization, ?Project $project): array;

    /**
     * Segundo intento cuando el documento no validó.
     *
     * @param  array<string,mixed>  $document  el documento que falló
     * @param  array<int,string>  $errors  errores del validador
     * @return array<string,mixed>
     */
    public function repair(Brief $brief, array $document, array $errors, Organization $organization, ?Project $project): array;
}
