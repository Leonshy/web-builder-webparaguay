<?php

return [
    // fake (default, dev/CI) | whmcs
    'billing_driver' => env('PUBLISHING_BILLING', 'fake'),
    // fake (default) | plesk
    'hosting_driver' => env('PUBLISHING_HOSTING', 'fake'),

    // Tag del paquete site-runtime que corren TODAS las instancias.
    'runtime_version' => env('PUBLISHING_RUNTIME_VERSION', '0.1.0'),
    'plan_price' => (int) env('PUBLISHING_PLAN_PRICE', 39900), // guaraníes/mes, debe coincidir con el producto WHMCS

    'subdomain_base' => env('PUBLISHING_SUBDOMAIN_BASE', 'sites.naranja.com.py'),

    // Deploy por git: el servidor Plesk hace pull de este repo.
    'git_repo_url' => env('PUBLISHING_GIT_REPO', 'git@github.com:Leonshy/web-builder-webparaguay.git'),

    // Credenciales: NUNCA en el repo. Sólo por env, con lista blanca de IP.
    'whmcs' => [
        'url' => env('WHMCS_URL', ''),
        'identifier' => env('WHMCS_IDENTIFIER', ''),
        'secret' => env('WHMCS_SECRET', ''),
    ],
    'payment_mode' => env('WHMCS_PAYMENT_MODE', 'manual'), // manual | auto
    'whmcs_product_id' => (int) env('WHMCS_PRODUCT_ID', 130),
    'whmcs_billing_cycle' => env('WHMCS_BILLING_CYCLE', 'monthly'),
    'whmcs_payment_method' => env('WHMCS_PAYMENT_METHOD', 'banktransfer'),
    'whmcs_cf_tax_id' => (int) env('WHMCS_CF_TAX_ID', 1),  // id del campo personalizado 'RUC o CI'
    'whmcs_cf_company' => (int) env('WHMCS_CF_COMPANY', 2), // id del campo 'Razón social'

    'plesk' => [
        'service_plan' => env('PLESK_SERVICE_PLAN', 'IA-host'),
        'db_server' => env('PLESK_DB_SERVER', 'localhost'),
        'php_bin' => env('PLESK_PHP_BIN', '/opt/plesk/php/8.4/bin/php'),
        'letsencrypt_email' => env('PLESK_LETSENCRYPT_EMAIL', 'soporte@webparaguay.com'),
        // El repo debe ser clonable por el servidor sin llave (HTTPS público).
        'git_repo_url' => env('PLESK_GIT_REPO', 'https://github.com/Leonshy/web-builder-webparaguay.git'),
        // El aprovisionamiento (git + Let's Encrypt) va por SSH: la API REST de
        // Plesk no los cubre.
        'ssh' => [
            'host' => env('PLESK_SSH_HOST', ''),
            'port' => (int) env('PLESK_SSH_PORT', 22),
            'user' => env('PLESK_SSH_USER', 'root'),
            'private_key' => env('PLESK_SSH_KEY', ''),   // ruta al archivo o PEM
            'password' => env('PLESK_SSH_PASSWORD', ''),
        ],
    ],
];
