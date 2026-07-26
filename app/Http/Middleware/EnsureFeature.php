<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\License\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blokkeert routes die een feature vereisen die niet in de licentie zit.
 * Gebruik: ->middleware('feature:automations').
 */
class EnsureFeature
{
    public function __construct(private readonly LicenseService $license) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless(
            $this->license->allowsFeature($feature),
            402, // Payment Required
            "De functie \"{$feature}\" zit niet in je licentiepakket.",
        );

        return $next($request);
    }
}
