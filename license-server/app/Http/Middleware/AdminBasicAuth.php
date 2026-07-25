<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Eenvoudige HTTP basic-auth voor het adminpaneel van de licentieserver.
 */
class AdminBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = (string) config('license.admin_user');
        $password = (string) config('license.admin_password');

        $ok = $password !== ''
            && hash_equals($user, (string) $request->getUser())
            && hash_equals($password, (string) $request->getPassword());

        if (! $ok) {
            return response('Authenticatie vereist.', 401, ['WWW-Authenticate' => 'Basic realm="License admin"']);
        }

        return $next($request);
    }
}
