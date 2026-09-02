<?php

return [
    // fake (default, dev/CI) | whmcs
    'billing_driver' => env('PUBLISHING_BILLING', 'fake'),
    // fake (default) | plesk
    'hosting_driver' => env('PUBLISHING_HOSTING', 'fake'),

    // Tag del paquete site-runtime que corren TODAS las instancias.
    'runtime_version' => env('PUBLISHING_RUNTIME_VERSION', '0.1.0'),

    'subdomain_base' => env('PUBLISHING_SUBDOMAIN_BASE', 'webparaguay.com'),

    // Deploy por git: el servidor Plesk hace pull de este repo.
    'git_repo_url' => env('PUBLISHING_GIT_REPO', 'git@github.com:Leonshy/web-builder-webparaguay.git'),

    // Credenciales: NUNCA en el repo. Sólo por env, con lista blanca de IP.
    'whmcs' => [
        'url' => env('WHMCS_URL', ''),
        'identifier' => env('WHMCS_IDENTIFIER', ''),
        'secret' => env('WHMCS_SECRET', ''),
    ],
    'plesk' => [
        'url' => env('PLESK_URL', ''),
        'api_key' => env('PLESK_API_KEY', ''),
        'service_plan' => env('PLESK_SERVICE_PLAN', 'webparaguay-cms'),
    ],
];
