<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiDocsController extends Controller
{
    /**
     * OpenAPI 3.1-specificatie van de publieke API.
     *
     * @return array<string, mixed>
     */
    public function spec(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'rmboard API',
                'version' => (string) config('board.version'),
                'description' => 'REST API voor rmboard. Authenticatie via een persoonlijk '
                    .'API-token (Bearer). Rate limiting per token.',
            ],
            'servers' => [['url' => rtrim((string) config('app.url'), '/').'/api/v1']],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer'],
                ],
            ],
            'security' => [['bearerAuth' => []]],
            'paths' => [
                '/boards' => [
                    'get' => [
                        'summary' => 'Lijst van toegankelijke boards',
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                ],
                '/boards/{board}/cards' => [
                    'get' => [
                        'summary' => 'Kaarten van een board',
                        'parameters' => [[
                            'name' => 'board', 'in' => 'path', 'required' => true,
                            'schema' => ['type' => 'integer'],
                        ]],
                        'responses' => ['200' => ['description' => 'OK'], '403' => ['description' => 'Geen toegang']],
                    ],
                ],
            ],
        ];
    }

    public function json(): JsonResponse
    {
        return response()->json($this->spec());
    }

    /**
     * Zelf-gehoste docs-pagina (geen CDN): rendert de spec leesbaar.
     */
    public function page(): Response
    {
        $json = json_encode($this->spec(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $html = <<<HTML
        <!doctype html><html lang="nl"><head><meta charset="utf-8">
        <title>rmboard API-documentatie</title>
        <style>body{font:14px/1.6 system-ui,sans-serif;max-width:900px;margin:2rem auto;padding:0 1rem}
        pre{background:#0b1020;color:#e2e8f0;padding:1rem;border-radius:8px;overflow:auto}</style>
        </head><body>
        <h1>rmboard API</h1>
        <p>OpenAPI 3.1-specificatie. Authenticatie via <code>Authorization: Bearer &lt;token&gt;</code>.</p>
        <pre>{$json}</pre>
        </body></html>
        HTML;

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }
}
