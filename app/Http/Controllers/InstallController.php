<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\InstallRequest;
use App\Services\InstallService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InstallController extends Controller
{
    public function __construct(private readonly InstallService $installer) {}

    /**
     * Toon de meerstaps installatiewizard.
     */
    public function show(): Response
    {
        return Inertia::render('Install/Wizard', [
            'locales' => config('board.locales'),
            'defaultLocale' => config('board.default_locale'),
            'timezones' => \DateTimeZone::listIdentifiers(),
            'defaultTimezone' => 'Europe/Amsterdam',
            'version' => config('board.version'),
            // Toon de licentiestap alleen als deze build een publieke sleutel kent
            // (d.w.z. commercieel gelicentieerd) én handhaving aan staat.
            'licensing' => (string) config('license.public_key') !== '' && (bool) config('license.enforce'),
        ]);
    }

    /**
     * Voer de installatie uit en log de nieuwe beheerder in.
     */
    public function store(InstallRequest $request): RedirectResponse
    {
        /** @var array{
         *     app_name: string, locale: string, timezone: string,
         *     admin_name: string, admin_username: string,
         *     admin_email: string, admin_password: string,
         *     license_key?: string|null
         * } $data */
        $data = $request->validated();

        $admin = $this->installer->install($data);

        Auth::login($admin, remember: true);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', 'Welkom bij rmboard! Je installatie is klaar.');
    }

    /**
     * Optionele SMTP-test tijdens de installatie.
     */
    public function testMail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'email'],
        ]);

        // In Fase 4 wordt de daadwerkelijke test-mail via een Mailable verstuurd.
        // Voor nu bevestigen we dat het endpoint bereikbaar is.
        return back()->with('mail_test', "Test-mail zou verstuurd worden naar {$validated['to']}.");
    }
}
