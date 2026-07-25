<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Licentie — in de code gebakken (geen compose-/env-knop voor de klant)
|--------------------------------------------------------------------------
|
| Board is "altijd gelicentieerd": zonder geldige licentie draait de app in
| een geblokkeerde modus. Als LEVERANCIER vul je hieronder je eigen publieke
| Ed25519-sleutel in (committed in de broncode) en laat je enforce op true.
|
| Deze waarden staan bewust NIET in docker-compose.yml, zodat een klant ze
| niet als losse instelling ziet/aanpast. (Bij broncode-distributie blijft de
| code technisch aanpasbaar — de licentieovereenkomst/EULA is de uiteindelijke
| bescherming; deze opzet maakt omzeilen een bewuste ingreep i.p.v. één regel.)
|
*/

// >>> LEVERANCIER: vul hier je publieke sleutel + (optioneel) server-URL in. <<<
$bakedPublicKey = '';   // bijv. 'MCowBQYDK2VwAyEA...'  (php artisan license:keygen op de licentieserver)
$bakedServerUrl = '';   // bijv. 'https://licenties.jouwdomein.nl'
$enforceByDefault = true;

return [
    /*
     * Handhaving. Standaard aan en in de code verankerd. (Een env-override
     * blijft technisch mogelijk voor de leverancier zelf, maar wordt nergens
     * aan de klant aangeboden.)
     */
    'enforce' => (bool) env('LICENSE_ENFORCE', $enforceByDefault),

    /*
     * Publieke Ed25519-sleutel van de licentieserver — hiermee verifieert Board
     * de licentiesleutel lokaal (offline). Wordt in de code gebakken.
     */
    'public_key' => env('LICENSE_PUBLIC_KEY', $bakedPublicKey),

    /*
     * Licentieserver voor online upgrades/intrekking-controle.
     */
    'server_url' => env('LICENSE_SERVER_URL', $bakedServerUrl),

    'product' => 'board',

    /*
     * Terugvaltier zonder (geldige) licentie. In "altijd gelicentieerd"-modus
     * blokkeert de EnsureLicensed-middleware de app; deze limieten dienen als
     * secundaire rem (bv. registratie) mocht er toch iets doorheen glippen.
     */
    'unlicensed' => [
        'limits' => [
            'users' => 1,
            'workspaces' => 1,
            'boards' => 1,
            'storage_gb' => 1,
        ],
        'features' => [],
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
