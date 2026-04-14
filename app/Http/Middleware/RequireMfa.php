<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // MFA niet ingeschakeld → gewoon doorlaten (MFA is optioneel)
        if (!$user->mfa_enabled) {
            return $next($request);
        }

        // MFA ingesteld maar sessie nog niet geverifieerd → stuur naar verify
        if (!$request->session()->get('mfa_verified')) {
            if (!$request->routeIs('mfa.*')) {
                return redirect()->route('mfa.verify');
            }
        }

        return $next($request);
    }
}
