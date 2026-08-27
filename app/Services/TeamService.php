<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;

/**
 * Maakt een nieuw team aan en koppelt een gebruiker als eigenaar.
 * Gebruikt bij zelfregistratie, admin-aangemaakte accounts, en het
 * accepteren van een team-uitnodiging (voor de uitnodigende partij's team
 * gebeurt dit uiteraard niet opnieuw — zie TeamInvitationController).
 */
class TeamService
{
    public function createForOwner(User $user): Team
    {
        $team = Team::create([
            'name'     => $user->company_name ?: ($user->name . '’s team'),
            'owner_id' => $user->id,
        ]);

        $team->members()->attach($user->id, ['role' => Team::ROLE_OWNER]);

        $user->current_team_id = $team->id;
        $user->save();

        return $team;
    }
}
