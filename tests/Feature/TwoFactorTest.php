<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\TwoFactorService;

beforeEach(function (): void {
    clearInstallState();
    installApp();
});

it('schakelt 2FA in en bevestigt met een geldige code', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/user/two-factor/enable');
    $response->assertOk()->assertJsonStructure(['secret', 'otpauth_uri']);

    $secret = $response->json('secret');
    $code = app(TwoFactorService::class)->codeAt($secret);

    $confirm = $this->actingAs($user)->postJson('/user/two-factor/confirm', ['code' => $code]);
    $confirm->assertOk()->assertJsonStructure(['recovery_codes']);

    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

it('weigert bevestiging met een verkeerde code', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/user/two-factor/enable');

    $this->actingAs($user)
        ->postJson('/user/two-factor/confirm', ['code' => '000000'])
        ->assertStatus(422);

    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('vraagt bij inloggen om een 2FA-code en logt daarna in', function (): void {
    $service = app(TwoFactorService::class);
    $secret = $service->generateSecret();

    $user = User::factory()->create(['password' => Hash::make('geheim123')]);
    $user->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['AAAAA-BBBBB'],
    ])->save();

    // Wachtwoord klopt maar 2FA staat aan → nog niet ingelogd.
    $this->post('/login', ['login' => $user->email, 'password' => 'geheim123'])
        ->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();

    // Geldige code → ingelogd.
    $this->post('/two-factor-challenge', ['code' => $service->codeAt($secret)])
        ->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

it('accepteert een herstelcode en verbruikt hem', function (): void {
    $user = User::factory()->create(['password' => Hash::make('geheim123')]);
    $user->forceFill([
        'two_factor_secret' => app(TwoFactorService::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['AAAAA-BBBBB', 'CCCCC-DDDDD'],
    ])->save();

    $this->post('/login', ['login' => $user->email, 'password' => 'geheim123']);

    $this->post('/two-factor-challenge', ['recovery_code' => 'AAAAA-BBBBB'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->two_factor_recovery_codes)->toBe(['CCCCC-DDDDD']);
});
