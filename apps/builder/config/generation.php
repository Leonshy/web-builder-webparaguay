<?php

return [
    // template | claude
    'driver' => env('BUILDER_GENERATOR', 'template'),
    'daily_limit' => (int) env('BUILDER_DAILY_GENERATION_LIMIT', 20),

    // API interna de site-runtime para el handoff del documento generado.
    'site_runtime_url' => env('SITE_RUNTIME_URL', 'http://127.0.0.1:8000'),
    'site_runtime_token' => env('SITE_RUNTIME_INTERNAL_TOKEN', ''),
];
