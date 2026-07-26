<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    clearInstallState();
    installApp();
});

it('levert Prometheus-metrics', function (): void {
    config(['board.metrics_token' => '']);
    User::factory()->count(3)->create();

    $response = $this->get('/metrics');
    $response->assertOk();
    expect($response->getContent())
        ->toContain('board_users_total')
        ->toContain('# TYPE board_boards_total gauge');
});

it('schermt metrics af met een token', function (): void {
    config(['board.metrics_token' => 'geheim']);

    $this->get('/metrics')->assertForbidden();
    $this->get('/metrics?token=geheim')->assertOk();
    $this->withToken('geheim')->get('/metrics')->assertOk();
});

it('verbergt OIDC-routes als SSO uit staat', function (): void {
    config(['board.oidc.enabled' => false]);

    $this->get('/oidc/redirect')->assertNotFound();
});

it('start de OIDC-flow als SSO aan staat', function (): void {
    config([
        'board.oidc.enabled' => true,
        'board.oidc.client_id' => 'board-client',
        'board.oidc.issuer' => 'https://idp.voorbeeld.nl',
    ]);

    Http::fake([
        'idp.voorbeeld.nl/.well-known/openid-configuration' => Http::response([
            'authorization_endpoint' => 'https://idp.voorbeeld.nl/authorize',
            'token_endpoint' => 'https://idp.voorbeeld.nl/token',
            'userinfo_endpoint' => 'https://idp.voorbeeld.nl/userinfo',
        ]),
    ]);

    $response = $this->get('/oidc/redirect');
    $response->assertRedirect();
    expect($response->headers->get('Location'))
        ->toContain('https://idp.voorbeeld.nl/authorize')
        ->toContain('client_id=board-client');
});

it('serveert de zelf-gehoste API-docs en offline-pagina', function (): void {
    $this->get('/api/docs')->assertOk()->assertSee('rmboard API');
    $this->get('/api/openapi.json')->assertOk()->assertJsonPath('openapi', '3.1.0');
    $this->get('/offline')->assertOk()->assertSee('offline');
});

it('toont de versie op de health-endpoint', function (): void {
    $this->getJson('/api/health')->assertOk()->assertJsonStructure(['version', 'installed', 'services']);
});
