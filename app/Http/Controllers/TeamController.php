<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function show()
    {
        $team = Auth::user()->currentTeam;

        $members     = $team->members()->orderBy('name')->get();
        $invitations = $team->invitations()->whereNull('accepted_at')->orderByDesc('created_at')->get();

        return view('team.show', compact('team', 'members', 'invitations'));
    }

    public function invite(Request $request)
    {
        abort_unless(Auth::user()->isOwnerOfCurrentTeam(), 403);

        $team = Auth::user()->currentTeam;

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($team->members()->where('email', $validated['email'])->exists()) {
            return back()->withErrors(['email' => 'Dit e-mailadres is al lid van het team.']);
        }

        if ($team->invitations()->whereNull('accepted_at')->where('email', $validated['email'])->exists()) {
            return back()->withErrors(['email' => 'Er staat al een openstaande uitnodiging voor dit e-mailadres.']);
        }

        $invitation = TeamInvitation::create([
            'team_id'    => $team->id,
            'email'      => $validated['email'],
            'token'      => Str::random(64),
            'role'       => Team::ROLE_MEMBER,
            'invited_by' => Auth::id(),
        ]);

        $inviteUrl = route('team-invitations.accept', ['token' => $invitation->token]);
        $mailer    = new MailService();
        $sent      = $mailer->sendInvite($invitation->email, $invitation->email, $team->name, $inviteUrl);

        $msg = $sent
            ? 'Uitnodiging verstuurd naar ' . $invitation->email . '.'
            : 'Uitnodiging aangemaakt maar de mail kon niet worden verstuurd. Controleer de e-mailinstellingen.';

        return back()->with($sent ? 'success' : 'warning', $msg);
    }

    public function cancelInvite(TeamInvitation $invitation)
    {
        abort_unless(Auth::user()->isOwnerOfCurrentTeam(), 403);
        abort_unless($invitation->team_id === Auth::user()->currentTeam->id, 403);

        $invitation->delete();

        return back()->with('success', 'Uitnodiging ingetrokken.');
    }

    public function removeMember(\App\Models\User $user)
    {
        abort_unless(Auth::user()->isOwnerOfCurrentTeam(), 403);

        $team = Auth::user()->currentTeam;

        if ($user->id === $team->owner_id) {
            return back()->withErrors(['error' => 'De eigenaar kan niet uit het team worden verwijderd.']);
        }

        abort_unless($team->members()->where('users.id', $user->id)->exists(), 404);

        $team->members()->detach($user->id);

        if ($user->current_team_id === $team->id) {
            $user->current_team_id = null;
            $user->save();
        }

        return back()->with('success', $user->name . ' is uit het team verwijderd.');
    }
}
