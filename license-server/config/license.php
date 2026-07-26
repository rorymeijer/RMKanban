<?php

declare(strict_types=1);

return [
    'product' => 'board',

    /*
     * Ed25519-sleutelpaar (base64). Genereer met: php artisan license:keygen
     * en zet de waarden in .env. De publieke sleutel geef je aan Board
     * (LICENSE_PUBLIC_KEY); de privésleutel blijft geheim op de licentieserver.
     */
    'private_key' => env('LICENSE_PRIVATE_KEY', ''),
    'public_key' => env('LICENSE_PUBLIC_KEY', ''),

    /*
     * Basic-auth voor het adminpaneel.
     */
    'admin_user' => env('ADMIN_USER', 'admin'),
    'admin_password' => env('ADMIN_PASSWORD', ''),

    /*
     * Beschikbare feature-flags waaruit een pakket kan kiezen.
     */
    'features' => [
        'automations',
        'api',
        'webhooks',
        'sso',
        'custom_fields',
        'realtime',
        'search',
    ],
];
