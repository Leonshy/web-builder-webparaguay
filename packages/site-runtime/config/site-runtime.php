<?php

use Webparaguay\Schema\Schema;

return [
    /*
     * El contrato formal vive en packages/schema (webparaguay/schema).
     * Se puede sobreescribir la ruta para pruebas.
     */
    'schema_path' => env('SITE_RUNTIME_SCHEMA_PATH', Schema::path()),

    /*
     * Sitios de ejemplo que sirven las rutas de desarrollo.
     */
    'preview_site_path' => env('SITE_RUNTIME_PREVIEW_PATH', Schema::examplePath()),
    'variants_site_path' => env('SITE_RUNTIME_VARIANTS_PATH', base_path('resources/fixtures/variants-gallery.json')),
];
