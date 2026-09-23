<?php

namespace Tests\Feature;

use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Een eigenaar kan een teamlid alleen aanvragen: een extra lid moet eerst in
 * Salesforce gefactureerd worden. Pas na goedkeuring door een admin gaat de
 * uitnodiging de deur uit.
 */
class TeamMemberRequestTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email, bool $admin = false): User
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
        $user->forceFill(['is_admin' => $admin])->save();

        (new TeamService())->createForOwner($user);

        return $user;
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    /** @return string[] ontvangers van alle verstuurde mails */
    private function sentTo(): array
    {
        return app('mailer')->getSymfonyTransport()->messages()
            ->map(fn ($m) => $m->getEnvelope()->getRecipients()[0]->getAddress())
            ->all();
    }

    public function test_aanvragen_mailt_de_admin_en_niet_het_nieuwe_lid(): void
    {
        $this->makeUser('admin@example.test', admin: true);
        $owner = $this->makeUser('eigenaar@example.test');

        $this->as($owner)->post(route('team.invite'), ['email' => 'nieuw@example.test'])
            ->assertSessionHas('success');

        $invitation = TeamInvitation::firstOrFail();
        $this->assertFalse($invitation->isApproved());
        $this->assertSame(['admin@example.test'], $this->sentTo());

        // De link werkt nog niet, ook niet als iemand de token weet
        $this->get(route('team-invitations.accept', $invitation->token))->assertViewIs('auth.invite-invalid');
        $this->post(route('team-invitations.activate', $invitation->token), [
            'name' => 'Nieuw', 'password' => 'Wachtwoord-123!', 'password_confirmation' => 'Wachtwoord-123!',
        ]);
        $this->assertNull(User::where('email', 'nieuw@example.test')->first());
    }

    public function test_goedkeuren_door_admin_verstuurt_de_uitnodiging(): void
    {
        $admin = $this->makeUser('admin@example.test', admin: true);
        $owner = $this->makeUser('eigenaar@example.test');
        $this->as($owner)->post(route('team.invite'), ['email' => 'nieuw@example.test']);
        $invitation = TeamInvitation::firstOrFail();

        $this->as($admin)->get(route('users.index'))->assertOk()->assertSee('nieuw@example.test');
        $this->as($admin)->post(route('team-requests.approve', $invitation))->assertSessionHas('success');

        $this->assertTrue($invitation->fresh()->isApproved());
        $this->assertContains('nieuw@example.test', $this->sentTo());
        $this->get(route('team-invitations.accept', $invitation->token))->assertViewIs('team.invitation-accept');
    }

    public function test_afwijzen_verwijdert_de_aanvraag(): void
    {
        $admin = $this->makeUser('admin@example.test', admin: true);
        $owner = $this->makeUser('eigenaar@example.test');
        $this->as($owner)->post(route('team.invite'), ['email' => 'nieuw@example.test']);

        $this->as($admin)->post(route('team-requests.reject', TeamInvitation::firstOrFail()))->assertSessionHas('success');

        $this->assertSame(0, TeamInvitation::count());
        $this->assertNotContains('nieuw@example.test', $this->sentTo());
    }

    public function test_eigenaar_kan_zijn_eigen_aanvraag_niet_goedkeuren(): void
    {
        $this->makeUser('admin@example.test', admin: true);
        $owner = $this->makeUser('eigenaar@example.test');
        $this->as($owner)->post(route('team.invite'), ['email' => 'nieuw@example.test']);
        $invitation = TeamInvitation::firstOrFail();

        $this->as($owner)->post(route('team-requests.approve', $invitation))->assertForbidden();
        $this->assertFalse($invitation->fresh()->isApproved());
    }

    public function test_gebruikersbeheer_toont_teams_met_meer_knop(): void
    {
        $admin  = $this->makeUser('admin@example.test', admin: true);
        $member = $this->makeUser('lid@example.test');

        foreach (range(1, 4) as $i) {
            $this->makeUser("eigenaar{$i}@example.test")->currentTeam->members()
                ->attach($member->id, ['role' => \App\Models\Team::ROLE_MEMBER]);
        }

        // 5 teams: eigen team + 4 → 3 zichtbaar, "+2" knop
        $this->as($admin)->get(route('users.index'))->assertOk()
            ->assertSee('User eigenaar1@example.test')
            ->assertSee('+2', false);
    }
}
