<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    /**
     * Start het inschakelen: genereer een geheim en toon de otpauth-URI.
     * Het geheim wordt pas definitief na bevestiging met een geldige code.
     */
    public function enable(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $secret = $this->twoFactor->generateSecret();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => null])->save();

        return response()->json([
            'secret' => $secret,
            'otpauth_uri' => $this->twoFactor->otpauthUri($secret, $user->email, config('app.name')),
        ]);
    }

    /**
     * Bevestig met een code uit de authenticator-app; genereer herstelcodes.
     */
    public function confirm(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate(['code' => ['required', 'string']]);

        $secret = $user->two_factor_secret;
        if (! is_string($secret) || ! $this->twoFactor->verify($secret, $validated['code'])) {
            throw ValidationException::withMessages(['code' => 'De code is ongeldig.']);
        }

        $recovery = $this->twoFactor->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $recovery,
        ])->save();

        return response()->json(['recovery_codes' => $recovery]);
    }

    public function disable(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('status', 'Tweestapsverificatie is uitgeschakeld.');
    }
}
