<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'users' => ['nullable', 'integer', 'min:1'],
            'workspaces' => ['nullable', 'integer', 'min:1'],
            'boards' => ['nullable', 'integer', 'min:1'],
            'storage_gb' => ['nullable', 'integer', 'min:1'],
            'features' => ['array'],
            'features.*' => [Rule::in((array) config('license.features'))],
            'trial_days' => ['nullable', 'integer', 'min:0'],
        ]);

        Package::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'limits' => [
                'users' => $data['users'] ?? null,
                'workspaces' => $data['workspaces'] ?? null,
                'boards' => $data['boards'] ?? null,
                'storage_gb' => $data['storage_gb'] ?? null,
            ],
            'features' => array_values($data['features'] ?? []),
            'trial_days' => $data['trial_days'] ?? 0,
        ]);

        return back()->with('status', 'Pakket aangemaakt.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        $package->delete();

        return back()->with('status', 'Pakket verwijderd.');
    }
}
