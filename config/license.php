<?php

declare(strict_types=1);

return [
    /*
     * Licentiehandhaving. Zet op false om Board zonder licentie onbeperkt te
     * draaien (bijv. open-source/self-hosted zonder commerciële limieten).
     */
    'enforce' => (bool) env('LICENSE_ENFORCE', true),

    /*
     * Publieke Ed25519-sleutel (base64) van de licentieserver — hiermee
     * verifieert Board de licentiesleutel lokaal, dus offline.
     */
    'public_key' => env('LICENSE_PUBLIC_KEY', ''),

    /*
     * Licentieserver voor online upgrades/intrekking-controle.
     */
    'server_url' => env('LICENSE_SERVER_URL', ''),

    'product' => 'board',

    /*
     * Terugvaltier zonder (geldige) licentie wanneer handhaving aan staat.
     * null = onbeperkt.
     */
    'unlicensed' => [
        'limits' => [
            'users' => (int) env('LICENSE_FREE_USERS', 3),
            'workspaces' => 1,
            'boards' => 3,
            'storage_gb' => 1,
        ],
        'features' => ['custom_fields'],
    ],

    /*
     * Beschikbare feature-flags die een pakket kan aan-/uitzetten.
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
