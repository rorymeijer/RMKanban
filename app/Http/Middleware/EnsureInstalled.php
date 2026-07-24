<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zolang er geen beheerder bestaat, staat de app in "needs setup"-modus en
 * worden alle routes doorgestuurd naar /install. Zodra geïnstalleerd is /install
 * permanent geblokkeerd.
 */
class EnsureInstalled
{
    /**
     * Routes die altijd bereikbaar zijn, ook vóór installatie.
     *
     * @var list<string>
     */
    private array $allowlist = [
        'install',
        'install/*',
        'api/health',
        'up',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $installed = Setting::isInstalled();
        $onInstallRoute = $request->is('install', 'install/*');

        if (! $installed) {
            // Nog niet geïnstalleerd: alles behalve de allowlist naar /install.
            if ($this->isAllowed($request)) {
                return $next($request);
            }

            return redirect()->route('install.show');
        }

        // Wel geïnstalleerd: de installer is permanent dicht.
        if ($onInstallRoute) {
            return redirect()->route('login');
        }

        return $next($request);
    }

    private function isAllowed(Request $request): bool
    {
        foreach ($this->allowlist as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
