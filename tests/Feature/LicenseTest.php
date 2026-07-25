<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use App\Services\License\License;
use App\Services\License\LicenseService;
use App\Services\License\LicenseToken;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    clearInstallState();
    installApp();

    $this->keys = LicenseToken::generateKeypair();
    config([
        'license.enforce' => true,
        'license.public_key' => $this->keys['public'],
        'license.server_url' => 'https://licenties.voorbeeld.nl',
    ]);
});

function makeToken(array $keys, array $overrides = []): string
{
    return LicenseToken::sign(array_merge([
        'id' => 'lic_1',
        'product' => 'board',
        'package' => 'Pro',
        'limits' => ['users' => null, 'workspaces' => null, 'boards' => null, 'storage_gb' => 10],
        'features' => ['automations', 'webhooks', 'sso', 'custom_fields', 'realtime', 'search', 'api'],
        'issued_at' => '2026-01-01T00:00:00+00:00',
        'expires_at' => null,
        'grace_days' => 14,
    ], $overrides), $keys['private']);
}

it('tekent en verifieert een token; knoeien maakt hem ongeldig', function (): void {
    $token = makeToken($this->keys, ['package' => 'Enterprise']);

    $payload = LicenseToken::verify($token, $this->keys['public']);
    expect($payload)->not->toBeNull();
    expect($payload['package'])->toBe('Enterprise');

    // Geknoeide payload.
    [$p, $s] = explode('.', $token);
    $tampered = rtrim(strtr(base64_encode('{"package":"Hacked","product":"board"}'), '+/', '-_'), '=').'.'.$s;
    expect(LicenseToken::verify($tampered, $this->keys['public']))->toBeNull();

    // Verkeerde publieke sleutel.
    $other = LicenseToken::generateKeypair();
    expect(LicenseToken::verify($token, $other['public']))->toBeNull();
});

it('valt zonder licentie terug op de geblokkeerde/onvergunde staat', function (): void {
    $license = app(LicenseService::class)->current();

    expect($license->unlicensed)->toBeTrue();
    expect($license->hasFeature('automations'))->toBeFalse();
    expect($license->limit('users'))->toBe(1);
});

it('activeert een geldige licentie en ontgrendelt features', function (): void {
    $service = app(LicenseService::class);
    $service->activate(makeToken($this->keys));

    $current = app(LicenseService::class)->current();
    expect($current->package)->toBe('Pro');
    expect($current->hasFeature('automations'))->toBeTrue();
    expect($current->limit('users'))->toBeNull(); // onbeperkt
});

it('weigert een ongeldige licentiesleutel', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/license', ['key' => 'onzin.token'])
        ->assertSessionHasErrors('key');
});

it('respecteert de respijtperiode en vervalt daarna', function (): void {
    // Verlopen, maar binnen 14 dagen respijt → nog bruikbaar.
    $inGrace = License::fromPayload([
        'id' => 'x', 'package' => 'Pro', 'limits' => [], 'features' => ['automations'],
        'expires_at' => now()->subDays(3)->toIso8601String(), 'grace_days' => 14,
    ]);
    expect($inGrace->isUsable())->toBeTrue();
    expect($inGrace->inGrace())->toBeTrue();

    // Voorbij de respijt → niet meer bruikbaar.
    $expired = License::fromPayload([
        'id' => 'x', 'package' => 'Pro', 'limits' => [], 'features' => ['automations'],
        'expires_at' => now()->subDays(30)->toIso8601String(), 'grace_days' => 14,
    ]);
    expect($expired->isUsable())->toBeFalse();
});

it('blokkeert de app zonder licentie en leidt de beheerder naar de licentiepagina', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/')->assertRedirect(route('admin.license'));

    // Met een geldige licentie werkt de app weer.
    app(LicenseService::class)->activate(makeToken($this->keys));
    $this->actingAs($admin)->get('/')->assertOk();
});

it('toont niet-beheerders een licentie-vereist-scherm zonder licentie', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/')->assertRedirect(route('license.required'));
    $this->actingAs($user)->get('/license-required')->assertOk();
});

it('blokkeert een feature-route zonder de juiste feature (met licentie)', function (): void {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $workspace->members()->attach($owner->id, ['role' => Role::Owner->value]);
    $board = Board::factory()->create(['workspace_id' => $workspace->id, 'visibility' => 'workspace']);

    // Geldige licentie zónder 'automations' → app werkt, maar feature is geblokkeerd (402).
    app(LicenseService::class)->activate(makeToken($this->keys, ['features' => ['custom_fields']]));
    $this->actingAs($owner)
        ->post("/boards/{$board->id}/automations", [
            'name' => 'Test', 'trigger' => 'card_moved', 'actions' => [['type' => 'archive']],
        ])
        ->assertStatus(402);

    // Licentie mét 'automations' → mag.
    app(LicenseService::class)->activate(makeToken($this->keys, ['features' => ['automations']]));
    $this->actingAs($owner)
        ->post("/boards/{$board->id}/automations", [
            'name' => 'Test', 'trigger' => 'card_moved', 'actions' => [['type' => 'archive']],
        ])
        ->assertRedirect();
});

it('trekt een licentie in via de online refresh', function (): void {
    app(LicenseService::class)->activate(makeToken($this->keys));
    expect(app(LicenseService::class)->current()->unlicensed)->toBeFalse();

    Http::fake([
        'licenties.voorbeeld.nl/api/licenses/*/status' => Http::response(['status' => 'revoked']),
    ]);

    app(LicenseService::class)->refresh();

    expect(app(LicenseService::class)->current()->unlicensed)->toBeTrue();
});
