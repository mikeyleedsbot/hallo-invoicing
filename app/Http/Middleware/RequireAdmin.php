<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blokkeert niet-admins. Alias 'admin' (zie bootstrap/app.php).
 * Aanvulling op de inline abort_unless-checks in de controllers
 * (defense in depth: route-niveau + controller-niveau).
 */
class RequireAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_admin, 403);

        return $next($request);
    }
}
