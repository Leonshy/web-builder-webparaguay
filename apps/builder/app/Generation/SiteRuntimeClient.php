<?php

namespace App\Generation;

use App\Models\Project;

/**
 * Handoff del documento validado a site-runtime, que lo importa como un sitio
 * editable y devuelve un enlace de preview. site-runtime tiene su propia base
 * (corre en Plesk): la única vía es su API interna.
 */
interface SiteRuntimeClient
{
    /**
     * @param  array<string,mixed>  $document  documento de sitio YA validado
     * @return array{site_ref:string, preview_url:string}
     */
    public function createSite(Project $project, string $name, array $document): array;
}
