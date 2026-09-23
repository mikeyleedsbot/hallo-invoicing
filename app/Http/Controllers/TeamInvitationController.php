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

        // Nog niet goedgekeurd = nog niet verstuurd; behandelen als onbekend
        if (!$invitation || !$invitation->isApproved()) {
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

        if (!$invitation || !$invitation->isApproved() || $invitation->isAccepted() || $invitation->isExpired()) {
            return redirect()->route('login')->withErrors(['email' => 'Ongeldige of verlopen uitnodigingslink.']);
        }

        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            // Bestaand account: koppelt direct aan het team, geen nieuw wachtwoord nodig.
            $switched = $this->joinTeam($existingUser, $invitation);

            $message = $switched
                ? 'Je bent toegevoegd aan het team. Log in om verder te gaan.'
                : 'Je bent toegevoegd aan het team. Je huidige team blijft actief — wissel via het menu rechtsboven zodra je bent ingelogd.';

            return redirect()->route('login')->with('success', $message);
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

    /**
     * Voegt de user toe aan het team van de uitnodiging. Als de user nog
     * geen actief team heeft (nieuw account, of een teamloos account),
     * wordt dit meteen het actieve team; anders blijft het huidige team
     * actief en moet de user zelf wisselen (zie TeamSwitchController) —
     * zo verdwijnt iemands werk nooit stilzwijgend uit beeld.
     *
     * @return bool of het nieuwe team meteen actief is gezet.
     */
    private function joinTeam(User $user, TeamInvitation $invitation): bool
    {
        $team = Team::findOrFail($invitation->team_id);

        if (!$team->members()->where('users.id', $user->id)->exists()) {
            $team->members()->attach($user->id, ['role' => $invitation->role]);
        }

        $switched = false;
        if (!$user->current_team_id) {
            $user->current_team_id = $team->id;
            $user->save();
            $switched = true;
        }

        $invitation->accepted_at = now();
        $invitation->save();

        return $switched;
    }
}
