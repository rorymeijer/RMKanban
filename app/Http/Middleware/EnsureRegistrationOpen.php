<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blokkeert de registratieroutes als zelfregistratie dicht staat (standaard).
 */
class EnsureRegistrationOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('board.registration_open')) {
            abort(404);
        }

        return $next($request);
    }
}
