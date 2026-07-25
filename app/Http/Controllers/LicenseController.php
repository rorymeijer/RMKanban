<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\License\LicenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class LicenseController extends Controller
{
    public function __construct(private readonly LicenseService $license) {}

    public function show(): Response
    {
        $current = $this->license->current();

        return Inertia::render('Admin/License', [
            'license' => [
                'package' => $current->package,
                'unlicensed' => $current->unlicensed,
                'expires_at' => $current->expiresAt?->toDateString(),
                'in_grace' => $current->inGrace(),
                'limits' => $current->limits,
                'features' => $current->features,
            ],
            'enforce' => (bool) config('license.enforce'),
        ]);
    }

    public function activate(Request $request): RedirectResponse
    {
        $data = $request->validate(['key' => ['required', 'string']]);

        try {
            $this->license->activate(trim($data['key']));
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['key' => $e->getMessage()]);
        }

        return back()->with('status', 'Licentie geactiveerd.');
    }

    public function refresh(): RedirectResponse
    {
        $this->license->refresh();

        return back()->with('status', 'Licentiestatus vernieuwd.');
    }
}
