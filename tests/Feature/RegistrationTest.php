<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;

beforeEach(function (): void {
    clearInstallState();
    installApp();
});

afterEach(function (): void {
    @unlink(storage_path('app/installed.lock'));
});

function registrationPayload(): array
{
    return [
        'name' => 'Nieuwe Gebruiker',
        'username' => 'nieuw',
        'email' => 'nieuw@example.com',
        'password' => 'Sterk-Wachtwoord-123!',
        'password_confirmation' => 'Sterk-Wachtwoord-123!',
    ];
}

it('verbergt registratie als die dicht staat', function (): void {
    config(['board.registration_open' => false]);

    $this->get('/register')->assertNotFound();
    $this->post('/register', registrationPayload())->assertNotFound();
    expect(User::count())->toBe(0);
});

it('laat registreren toe als die open staat en maakt een workspace', function (): void {
    config(['board.registration_open' => true]);

    $this->get('/register')->assertOk();
    $this->post('/register', registrationPayload())->assertRedirect(route('dashboard'));

    $user = User::firstWhere('email', 'nieuw@example.com');
    expect($user)->not->toBeNull();
    expect($user->is_admin)->toBeFalse();
    $this->assertAuthenticatedAs($user);

    $workspace = Workspace::firstWhere('owner_id', $user->id);
    expect($workspace)->not->toBeNull();
    expect($workspace->roleFor($user)?->value)->toBe('owner');
});
