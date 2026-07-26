<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LicenseController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
            'holder_name' => ['required', 'string', 'max:120'],
            'holder_email' => ['required', 'email', 'max:190'],
            'expires_at' => ['nullable', 'date'],
            'grace_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $license = License::create([
            'uuid' => (string) Str::uuid(),
            'package_id' => $data['package_id'],
            'holder_name' => $data['holder_name'],
            'holder_email' => $data['holder_email'],
            'status' => 'active',
            'expires_at' => $data['expires_at'] ?? null,
            'grace_days' => $data['grace_days'] ?? 14,
        ]);

        $license->issueKey();

        return back()->with('status', "Licentie uitgegeven voor {$license->holder_email}.");
    }

    /**
     * Upgrade een licentie naar een ander pakket en/of nieuwe vervaldatum;
     * de sleutel wordt opnieuw getekend zodat Board hem online oppikt.
     */
    public function upgrade(Request $request, License $license): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $license->forceFill([
            'package_id' => $data['package_id'],
            'expires_at' => $data['expires_at'] ?? $license->expires_at,
            'status' => 'active',
        ])->save();

        $license->issueKey();

        return back()->with('status', 'Licentie geüpgraded en opnieuw uitgegeven.');
    }

    public function revoke(License $license): RedirectResponse
    {
        $license->forceFill(['status' => 'revoked'])->save();

        return back()->with('status', 'Licentie ingetrokken.');
    }

    public function reactivate(License $license): RedirectResponse
    {
        $license->forceFill(['status' => 'active'])->save();
        $license->issueKey();

        return back()->with('status', 'Licentie heractiveerd.');
    }

    /**
     * Verkorte weergave met de sleutel (om te kopiëren naar de klant).
     */
    public function show(License $license): RedirectResponse
    {
        return back()->with('reveal_key', $license->uuid);
    }
}
