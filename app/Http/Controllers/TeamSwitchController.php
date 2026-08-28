<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamSwitchController extends Controller
{
    public function switch(Request $request, Team $team)
    {
        $user = Auth::user();

        abort_unless($user->teams()->where('teams.id', $team->id)->exists(), 403);

        $user->current_team_id = $team->id;
        $user->save();

        return back()->with('success', 'Je werkt nu in ' . $team->name . '.');
    }
}
