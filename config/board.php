<?php

declare(strict_types=1);

return [
    /*
     * Applicatiegeheim, gegenereerd door het entrypoint. Gebruikt als default
     * voor o.a. webhook-signing wanneer er geen specifieke sleutel is ingesteld.
     */
    'secret' => env('APP_SECRET'),

    /*
     * Versie, tijdens de build uit de git tag naar het VERSION-bestand geschreven.
     */
    'version' => is_file(base_path('VERSION'))
        ? (trim((string) file_get_contents(base_path('VERSION'))) ?: 'dev')
        : 'dev',

    /*
     * Staat zelfregistratie open? Standaard dicht; via env te openen.
     */
    'registration_open' => env('REGISTRATION_OPEN', false),

    /*
     * Maximale uploadgrootte in megabytes (afgedwongen op applicatieniveau).
     */
    'max_upload_size' => (int) env('MAX_UPLOAD_SIZE', 25),

    /*
     * Retentie van het audit-log in dagen.
     */
    'audit_retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),

    /*
     * Ondersteunde talen. Nederlands is standaard.
     */
    'locales' => ['nl', 'en'],
    'default_locale' => env('APP_LOCALE', 'nl'),

    /*
     * Reverb-doel voor de health-check (interne host/poort binnen het netwerk).
     */
    'reverb' => [
        'host' => env('REVERB_HOST', 'reverb'),
        'port' => (int) env('REVERB_PORT', 8080),
    ],

    /*
     * Token om de /metrics-endpoint af te schermen (leeg = open op intern netwerk).
     */
    'metrics_token' => env('METRICS_TOKEN', ''),

    /*
     * SSO via OIDC (optioneel naast wachtwoordlogin).
     */
    'oidc' => [
        'enabled' => (bool) env('OIDC_ENABLED', false),
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'issuer' => env('OIDC_ISSUER'),
        'scopes' => env('OIDC_SCOPES', 'openid profile email'),
    ],
];
