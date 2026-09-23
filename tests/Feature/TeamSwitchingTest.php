<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Iemand kan lid zijn van meerdere teams (bv. een boekhouder die voor twee
 * bedrijven facturen maakt). Wisselen moet expliciet via de switcher —
 * nooit stilzwijgend, en nooit data van een team tonen waar je geen lid
 * van bent.
 */
class TeamSwitchingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email): User
    {
        $user = User::create([
            'name'              => 'User ' . $email,
            'email'             => $email,
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ]);

        (new TeamService())->createForOwner($user);

        return $user;
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    public function test_bestaand_account_dat_tweede_uitnodiging_accepteert_blijft_in_huidig_team(): void
    {
        $person   = $this->makeUser('persoon@example.test');
        $firstTeam = $person->currentTeam;

        $inviter    = $this->makeUser('uitnodiger@example.test');
        $secondTeam = $inviter->currentTeam;

        $invitation = TeamInvitation::create([
            'team_id'    => $secondTeam->id,
            'email'      => $person->email,
            'token'      => Str::random(64),
            'role'       => Team::ROLE_MEMBER,
            'invited_by' => $inviter->id,
            'approved_at' => now(),
        ]);

        $this->post(route('team-invitations.activate', $invitation->token))
            ->assertRedirect(route('login'));

        $person->refresh();

        // Lid van beide teams...
        $this->assertTrue($person->teams()->where('teams.id', $firstTeam->id)->exists());
        $this->assertTrue($person->teams()->where('teams.id', $secondTeam->id)->exists());

        // ...maar het actieve team is niet stilzwijgend gewisseld.
        $this->assertEquals($firstTeam->id, $person->current_team_id);
    }

    public function test_wisselen_naar_ander_team_toont_diens_data(): void
    {
        $person = $this->makeUser('multi@example.test');
        $teamA  = $person->currentTeam;

        $owner = $this->makeUser('eigenaar-b@example.test');
        $teamB = $owner->currentTeam;
        $teamB->members()->attach($person->id, ['role' => Team::ROLE_MEMBER]);

        $this->actingAs($owner);
        $customerB = Customer::create(['name' => 'Klant van Team B', 'country' => 'Nederland']);

        // Nog in team A: klant van B niet zichtbaar.
        $this->as($person)->get('/customers')->assertOk()->assertDontSee($customerB->name);

        // Wissel expliciet naar team B.
        $this->as($person)->post(route('team.switch', $teamB))->assertRedirect();
        $person = $person->fresh();
        $this->assertEquals($teamB->id, $person->current_team_id);

        $this->as($person)->get('/customers')->assertOk()->assertSee($customerB->name);
    }

    public function test_kan_niet_wisselen_naar_team_waar_je_geen_lid_van_bent(): void
    {
        $person    = $this->makeUser('buitenstaander@example.test');
        $otherUser = $this->makeUser('ander@example.test');
        $otherTeam = $otherUser->currentTeam;

        $this->as($person)->post(route('team.switch', $otherTeam))->assertForbidden();
        $this->assertNotEquals($otherTeam->id, $person->fresh()->current_team_id);
    }
}
