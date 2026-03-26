<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->is_admin, 403);
        $users = User::orderBy('name')->get();
        return view('admin.users', compact('users'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'address'      => ['nullable', 'string', 'max:255'],
            'city'         => ['nullable', 'string', 'max:100'],
            'is_admin'     => ['boolean'],
        ]);

        $token = Str::random(64);

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'company_name' => $validated['company_name'] ?? null,
            'phone'        => $validated['phone'] ?? null,
            'address'      => $validated['address'] ?? null,
            'city'         => $validated['city'] ?? null,
            'is_admin'     => $request->boolean('is_admin'),
            'password'     => Hash::make(Str::random(32)), // tijdelijk wachtwoord
            'invite_token' => $token,
            'invite_sent_at' => now(),
        ]);

        // Stuur uitnodigingsmail
        $inviteUrl = route('invite.accept', ['token' => $token]);
        $mailer    = new MailService();
        $sent      = $mailer->sendInvite($user->email, $user->name, $user->company_name ?? '', $inviteUrl);

        $msg = $sent
            ? 'Gebruiker aangemaakt en uitnodiging verstuurd naar ' . $user->email . '.'
            : 'Gebruiker aangemaakt maar uitnodigingsmail kon niet worden verstuurd. Controleer de e-mailinstellingen.';

        return redirect()->route('users.index')->with($sent ? 'success' : 'warning', $msg);
    }

    public function update(Request $request, User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:users,email,' . $user->id],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'address'      => ['nullable', 'string', 'max:255'],
            'city'         => ['nullable', 'string', 'max:100'],
            'is_admin'     => ['boolean'],
        ]);

        $user->name         = $validated['name'];
        $user->email        = $validated['email'];
        $user->company_name = $validated['company_name'] ?? null;
        $user->phone        = $validated['phone'] ?? null;
        $user->address      = $validated['address'] ?? null;
        $user->city         = $validated['city'] ?? null;
        $user->is_admin     = $request->boolean('is_admin');
        $user->save();

        return redirect()->route('users.index')->with('success', 'Gebruiker bijgewerkt.');
    }

    public function resendInvite(User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $token = Str::random(64);
        $user->invite_token   = $token;
        $user->invite_sent_at = now();
        $user->save();

        $inviteUrl = route('invite.accept', ['token' => $token]);
        $mailer    = new MailService();
        $sent      = $mailer->sendInvite($user->email, $user->name, $user->company_name ?? '', $inviteUrl);

        return back()->with(
            $sent ? 'success' : 'warning',
            $sent ? 'Uitnodiging opnieuw verstuurd naar ' . $user->email . '.' : 'Versturen mislukt. Controleer de e-mailinstellingen.'
        );
    }

    public function destroy(User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Je kunt jezelf niet verwijderen.']);
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'Gebruiker verwijderd.');
    }

    public function resetMfa(User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $user->mfa_enabled      = false;
        $user->mfa_secret       = null;
        $user->mfa_confirmed_at = null;
        $user->save();

        return back()->with('success', 'MFA gereset voor ' . $user->name . '.');
    }
}
