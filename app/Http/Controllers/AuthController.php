<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login', [
            'registrationOpen' => (bool) config('board.registration_open'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::query()->where($field, $credentials['login'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => 'Deze inloggegevens zijn onbekend.',
            ]);
        }

        // 2FA: nog niet inloggen, eerst de code uitvragen.
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('login.2fa', [
                'id' => $user->id,
                'remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showTwoFactorChallenge(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('login.2fa')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function twoFactorChallenge(Request $request): RedirectResponse
    {
        /** @var array{id:int, remember:bool}|null $pending */
        $pending = $request->session()->get('login.2fa');
        if ($pending === null) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = User::query()->findOrFail($pending['id']);
        $secret = $user->two_factor_secret;

        $verified = false;

        if (! empty($validated['code']) && is_string($secret)) {
            $verified = $this->twoFactor->verify($secret, $validated['code']);
        } elseif (! empty($validated['recovery_code'])) {
            $verified = $this->consumeRecoveryCode($user, $validated['recovery_code']);
        }

        if (! $verified) {
            throw ValidationException::withMessages([
                'code' => 'De verificatiecode is ongeldig.',
            ]);
        }

        $request->session()->forget('login.2fa');
        Auth::login($user, (bool) $pending['remember']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes;
        if (! is_array($codes)) {
            return false;
        }

        $remaining = array_values(array_filter($codes, fn ($c): bool => ! hash_equals((string) $c, $code)));

        if (count($remaining) === count($codes)) {
            return false; // niets verwijderd → code bestond niet
        }

        $user->forceFill(['two_factor_recovery_codes' => $remaining])->save();

        return true;
    }
}
