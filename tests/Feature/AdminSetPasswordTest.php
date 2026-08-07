<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Beheerders kunnen rechtstreeks een wachtwoord instellen voor een klant,
 * voor het geval de resetmail niet aankomt.
 */
class AdminSetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $klant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeUser('beheerder@example.test', ['is_admin' => true]);
        $this->klant = $this->makeUser('klant@example.test');
    }

    private function makeUser(string $email, array $attributes = []): User
    {
        return User::create(array_merge([
            'name'              => 'Gebruiker ' . $email,
            'email'             => $email,
            'password'          => bcrypt('oud-wachtwoord'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ], $attributes));
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    public function test_beheerder_kan_wachtwoord_instellen(): void
    {
        $this->as($this->admin)
            ->post(route('users.set-password', $this->klant), [
                'password'              => 'Nieuw-Wachtwoord-9!',
                'password_confirmation' => 'Nieuw-Wachtwoord-9!',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->klant->refresh();

        $this->assertTrue(Hash::check('Nieuw-Wachtwoord-9!', $this->klant->password));
        $this->assertFalse(Hash::check('oud-wachtwoord', $this->klant->password));
    }

    public function test_klant_kan_met_het_nieuwe_wachtwoord_inloggen(): void
    {
        $this->as($this->admin)->post(route('users.set-password', $this->klant), [
            'password'              => 'Nieuw-Wachtwoord-9!',
            'password_confirmation' => 'Nieuw-Wachtwoord-9!',
        ]);

        $this->assertTrue(auth()->validate([
            'email'    => $this->klant->email,
            'password' => 'Nieuw-Wachtwoord-9!',
        ]));
    }

    public function test_onthoud_mij_token_wordt_vervangen(): void
    {
        $this->klant->forceFill(['remember_token' => 'oud-token'])->save();

        $this->as($this->admin)->post(route('users.set-password', $this->klant), [
            'password'              => 'Nieuw-Wachtwoord-9!',
            'password_confirmation' => 'Nieuw-Wachtwoord-9!',
        ]);

        $this->assertNotSame('oud-token', $this->klant->fresh()->remember_token);
    }

    public function test_actieve_sessies_van_de_klant_worden_beeindigd(): void
    {
        config(['session.driver' => 'database']);

        DB::table('sessions')->insert([
            ['id' => 'sessie-klant', 'user_id' => $this->klant->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'sessie-ander', 'user_id' => $this->admin->id, 'payload' => '', 'last_activity' => time()],
        ]);

        $this->as($this->admin)->post(route('users.set-password', $this->klant), [
            'password'              => 'Nieuw-Wachtwoord-9!',
            'password_confirmation' => 'Nieuw-Wachtwoord-9!',
        ]);

        // Alleen de sessie van de klant gaat eruit
        $this->assertDatabaseMissing('sessions', ['id' => 'sessie-klant']);
        $this->assertDatabaseHas('sessions', ['id' => 'sessie-ander']);
    }

    public function test_gewone_gebruiker_mag_dit_niet(): void
    {
        $anderKlant = $this->makeUser('ander@example.test');

        $this->as($this->klant)
            ->post(route('users.set-password', $anderKlant), [
                'password'              => 'Nieuw-Wachtwoord-9!',
                'password_confirmation' => 'Nieuw-Wachtwoord-9!',
            ])
            ->assertForbidden();

        $this->assertTrue(Hash::check('oud-wachtwoord', $anderKlant->fresh()->password));
    }

    public function test_uitgelogde_bezoeker_mag_dit_niet(): void
    {
        $this->post(route('users.set-password', $this->klant), [
            'password'              => 'Nieuw-Wachtwoord-9!',
            'password_confirmation' => 'Nieuw-Wachtwoord-9!',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('oud-wachtwoord', $this->klant->fresh()->password));
    }

    public function test_te_zwak_wachtwoord_wordt_geweigerd(): void
    {
        $this->as($this->admin)
            ->post(route('users.set-password', $this->klant), [
                'password'              => 'abc',
                'password_confirmation' => 'abc',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('oud-wachtwoord', $this->klant->fresh()->password));
    }

    public function test_bevestiging_moet_kloppen(): void
    {
        $this->as($this->admin)
            ->post(route('users.set-password', $this->klant), [
                'password'              => 'Nieuw-Wachtwoord-9!',
                'password_confirmation' => 'Iets-Anders-9!',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('oud-wachtwoord', $this->klant->fresh()->password));
    }

    public function test_mfa_blijft_staan(): void
    {
        $this->as($this->admin)->post(route('users.set-password', $this->klant), [
            'password'              => 'Nieuw-Wachtwoord-9!',
            'password_confirmation' => 'Nieuw-Wachtwoord-9!',
        ]);

        // Een nieuw wachtwoord mag de tweestapsverificatie niet uitzetten
        $this->assertTrue($this->klant->fresh()->mfa_enabled);
    }

    public function test_knop_staat_in_het_gebruikersoverzicht(): void
    {
        $this->as($this->admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Wachtwoord instellen');
    }
}
