<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use App\Models\Workspace;
use App\Services\InstallService;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    // Zorg voor een schone "niet geïnstalleerd"-staat.
    @unlink(storage_path('app/installed.lock'));
    Cache::flush();
});

afterEach(function (): void {
    @unlink(storage_path('app/installed.lock'));
});

function validInstallPayload(array $overrides = []): array
{
    return array_merge([
        'app_name' => 'Mijn Board',
        'locale' => 'nl',
        'timezone' => 'Europe/Amsterdam',
        'admin_name' => 'Beheerder',
        'admin_username' => 'beheerder',
        'admin_email' => 'admin@example.com',
        'admin_password' => 'Sterk-Wachtwoord-123!',
        'admin_password_confirmation' => 'Sterk-Wachtwoord-123!',
    ], $overrides);
}

it('stuurt alles naar de installer zolang er geen beheerder is', function (): void {
    $this->get('/login')->assertRedirect(route('install.show'));
    $this->get('/')->assertRedirect(route('install.show'));
});

it('toont de installatiewizard', function (): void {
    $this->get('/install')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Install/Wizard'));
});

it('laat de health-check ook vóór installatie toe', function (): void {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertJson(['installed' => false]);
});

it('maakt beheerder, workspace en demo-board aan en logt in', function (): void {
    $response = $this->post('/install', validInstallPayload());

    $response->assertRedirect(route('dashboard'));

    $admin = User::firstWhere('email', 'admin@example.com');
    expect($admin)->not->toBeNull();
    expect($admin->is_admin)->toBeTrue();
    $this->assertAuthenticatedAs($admin);

    $workspace = Workspace::firstWhere('owner_id', $admin->id);
    expect($workspace)->not->toBeNull();
    expect($workspace->boards()->count())->toBe(1);

    $board = $workspace->boards()->first();
    expect($board->lists()->count())->toBeGreaterThan(0);
    expect($board->cards()->count())->toBeGreaterThan(0);

    expect(Setting::isInstalled())->toBeTrue();
});

it('blokkeert de installer permanent na installatie', function (): void {
    $this->post('/install', validInstallPayload());
    $this->post('/logout');

    // De installer is nu dicht en verwijst naar login.
    $this->get('/install')->assertRedirect(route('login'));
});

it('valideert een zwak wachtwoord', function (): void {
    $this->post('/install', validInstallPayload([
        'admin_password' => 'zwak',
        'admin_password_confirmation' => 'zwak',
    ]))->assertSessionHasErrors('admin_password');

    expect(User::count())->toBe(0);
});

it('voorkomt een tweede beheerder (race-conditie)', function (): void {
    $this->post('/install', validInstallPayload());
    expect(User::count())->toBe(1);

    // Een tweede poging wordt door de middleware geblokkeerd.
    $this->post('/install', validInstallPayload([
        'admin_email' => 'tweede@example.com',
        'admin_username' => 'tweede',
    ]))->assertRedirect(route('login'));

    expect(User::count())->toBe(1);
});

it('gooit een fout als InstallService twee keer draait', function (): void {
    $service = app(InstallService::class);
    $service->install(validInstallPayload());

    expect(fn () => $service->install(validInstallPayload([
        'admin_email' => 'x@example.com',
        'admin_username' => 'x',
    ])))->toThrow(RuntimeException::class);

    expect(User::count())->toBe(1);
});
