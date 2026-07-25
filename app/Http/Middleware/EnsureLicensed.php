<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\License\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Altijd gelicentieerd": zonder geldige licentie draait Board in een
 * geblokkeerde modus. Beheerders worden naar de licentiepagina geleid om een
 * sleutel in te voeren; overige gebruikers zien een "licentie vereist"-scherm.
 */
class EnsureLicensed
{
    /**
     * Routes die ook zonder geldige licentie bereikbaar blijven, zodat een
     * beheerder een sleutel kan invoeren en iedereen kan uitloggen.
     *
     * @var list<string>
     */
    private array $allowlist = [
        'logout',
        'license-required',
        'admin/license',
        'admin/license/*',
        'notifications',
        'notifications/*',
    ];

    public function __construct(private readonly LicenseService $license) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('license.enforce')) {
            return $next($request);
        }

        $license = $this->license->current();
        if (! $license->unlicensed && $license->isUsable()) {
            return $next($request);
        }

        foreach ($this->allowlist as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        $user = $request->user();
        if ($user !== null && $user->is_admin) {
            return redirect()->route('admin.license')
                ->with('status', 'Voer een geldige licentiesleutel in om Board te activeren.');
        }

        return redirect()->route('license.required');
    }
}
