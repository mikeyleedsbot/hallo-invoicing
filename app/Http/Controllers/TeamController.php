<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            return back()->withErrors(['email' => 'Er staat al een openstaande aanvraag of uitnodiging voor dit e-mailadres.']);
        }

        // Geen directe uitnodiging: elk extra lid kost geld en moet eerst in
        // Salesforce gefactureerd worden. Een admin keurt goed en verstuurt dan
        // pas de uitnodiging (zie UserManagementController::approveMemberRequest).
        $invitation = TeamInvitation::create([
            'team_id'    => $team->id,
            'email'      => $validated['email'],
            'token'      => Str::random(64),
            'role'       => Team::ROLE_MEMBER,
            'invited_by' => Auth::id(),
        ]);

        $admins = User::where('is_admin', true)->where('status', User::STATUS_APPROVED)->get();
        $mailer = new MailService();

        foreach ($admins as $admin) {
            try {
                $mailer->sendTeamMemberRequestNotification($admin, $invitation);
            } catch (\Throwable $e) {
                Log::error('Teamlid-aanvraag notificatie mislukt', ['error' => $e->getMessage()]);
            }
        }

        return back()->with('success', 'Aanvraag voor ' . $invitation->email . ' ingediend. Na goedkeuring (facturatie) ontvangt diegene een uitnodiging.');
    }

    public function cancelInvite(TeamInvitation $invitation)
    {
        abort_unless(Auth::user()->isOwnerOfCurrentTeam(), 403);
        abort_unless($invitation->team_id === Auth::user()->currentTeam->id, 403);

        $invitation->delete();

        return back()->with('success', $invitation->isApproved() ? 'Uitnodiging ingetrokken.' : 'Aanvraag ingetrokken.');
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
