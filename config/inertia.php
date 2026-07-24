<?php

declare(strict_types=1);

return [
    /*
     * Voor het testen van pagina's controleert Inertia of de paginacomponent
     * bestaat. Onze pagina's staan in resources/js/pages met .tsx-extensie.
     */
    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [
            resource_path('js/pages'),
        ],
        'page_extensions' => [
            'tsx',
            'ts',
        ],
    ],
];
