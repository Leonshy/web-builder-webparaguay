<?php

return [
    /*
     * Ruta al contrato formal. En el monorepo es un espejo de packages/schema.
     * site-runtime lleva su propia copia para poder testear aislado (ADR-001).
     */
    'schema_path' => env('SITE_RUNTIME_SCHEMA_PATH', base_path('resources/schema/site.schema.json')),

    /*
     * Sitio de ejemplo que sirve la ruta de desarrollo /preview.
     */
    'preview_site_path' => env('SITE_RUNTIME_PREVIEW_PATH', base_path('resources/schema/example-site.json')),
];
