<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Impersonatie: admin kan tijdelijk meekijken als een andere gebruiker.
 *
 * - Alleen admins kunnen starten (route- én controller-check)
 * - Andere admins kunnen niet overgenomen worden
 * - De oorspronkelijke admin-id staat in de sessie; stoppen kan altijd
 *   via de balk onderin het scherm
 * - Start/stop wordt gelogd voor audit-doeleinden
 */
class ImpersonationController extends Controller
{
    public function start(Request $request, User $user)
    {
        abort_unless($request->user()?->is_admin, 403);
        abort_if($request->session()->has('impersonator_id'), 403, 'Je kijkt al mee als een andere gebruiker. Stop eerst de huidige sessie.');
        abort_if($user->id === $request->user()->id, 400, 'Je bent al ingelogd als dit account.');
        abort_if($user->is_admin, 403, 'Andere admins kunnen niet overgenomen worden.');
        abort_unless($user->isApproved(), 400, 'Alleen goedgekeurde accounts kunnen overgenomen worden.');

        Log::info('Impersonatie gestart', [
            'admin_id' => $request->user()->id,
            'admin_email' => $request->user()->email,
            'user_id' => $user->id,
            'user_email' => $user->email,
        ]);

        $request->session()->put('impersonator_id', $request->user()->id);

        Auth::login($user);

        // Admin heeft zijn eigen MFA al doorlopen; de MFA-check van het
        // overgenomen account niet opnieuw afdwingen.
        $request->session()->put('mfa_verified', true);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Je kijkt nu mee als ' . $user->name . '.');
    }

    public function stop(Request $request)
    {
        $adminId = $request->session()->pull('impersonator_id');
        abort_unless($adminId, 403);

        $admin = User::findOrFail($adminId);

        Log::info('Impersonatie gestopt', [
            'admin_id' => $admin->id,
            'user_id' => $request->user()?->id,
        ]);

        Auth::login($admin);
        $request->session()->put('mfa_verified', true);

        return redirect()
            ->route('users.index')
            ->with('success', 'Je bent terug op je eigen account.');
    }
}
