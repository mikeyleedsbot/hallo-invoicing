<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class TeamInvitationController extends Controller
{
    public function accept(string $token)
    {
        $invitation = TeamInvitation::where('token', $token)->first();

        if (!$invitation) {
            return view('auth.invite-invalid', ['reason' => 'not_found']);
        }

        if ($invitation->isAccepted()) {
            return redirect()->route('login')->withErrors(['email' => 'Deze uitnodiging is al geaccepteerd.']);
        }

        if ($invitation->isExpired()) {
            return view('auth.invite-invalid', ['reason' => 'expired']);
        }

        $existingUser = User::where('email', $invitation->email)->first();

        return view('team.invitation-accept', compact('invitation', 'token', 'existingUser'));
    }

    public function activate(Request $request, string $token)
    {
        $invitation = TeamInvitation::where('token', $token)->first();

        if (!$invitation || $invitation->isAccepted() || $invitation->isExpired()) {
            return redirect()->route('login')->withErrors(['email' => 'Ongeldige of verlopen uitnodigingslink.']);
        }

        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            // Bestaand account: koppelt direct aan het team, geen nieuw wachtwoord nodig.
            $this->joinTeam($existingUser, $invitation);

            return redirect()->route('login')->with('success', 'Je bent toegevoegd aan het team. Log in om verder te gaan.');
        }

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = User::create([
            'name'     => $request->input('name'),
            'email'    => $invitation->email,
            'password' => Hash::make($request->input('password')),
            'status'   => User::STATUS_APPROVED, // uitgenodigd worden door een teamlid is de goedkeuring
            'is_admin' => false,
        ]);

        $this->joinTeam($user, $invitation);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('mfa.setup')->with('success', 'Account geactiveerd! Stel nu tweestapsverificatie in.');
    }

    private function joinTeam(User $user, TeamInvitation $invitation): void
    {
        $team = Team::findOrFail($invitation->team_id);

        if (!$team->members()->where('users.id', $user->id)->exists()) {
            $team->members()->attach($user->id, ['role' => $invitation->role]);
        }

        $user->current_team_id = $team->id;
        $user->save();

        $invitation->accepted_at = now();
        $invitation->save();
    }
}
