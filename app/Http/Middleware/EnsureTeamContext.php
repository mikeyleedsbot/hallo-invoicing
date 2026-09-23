<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        if (!$user->current_team_id) {
            // Self-healing: user hoort nog wel bij een ander team (bv. na het
            // verlaten van zijn huidige team) — pak die in plaats van te blokkeren.
            $fallbackTeamId = $user->teams()->value('teams.id');

            if ($fallbackTeamId) {
                $user->current_team_id = $fallbackTeamId;
                $user->save();
            } else {
                abort(403, 'Je account is niet aan een team gekoppeld. Neem contact op met de beheerder.');
            }
        }

        return $next($request);
    }
}
