<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeUser('admin@example.test', isAdmin: true);
        $this->user  = $this->makeUser('user@example.test');
    }

    private function makeUser(string $email, bool $isAdmin = false): User
    {
        return User::create([
            'name'              => 'User ' . $email,
            'email'             => $email,
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
            'is_admin'          => $isAdmin,
        ]);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    public function test_admin_kan_meekijken_als_gebruiker(): void
    {
        $response = $this->as($this->admin)
            ->post(route('users.impersonate', $this->user));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
        $this->assertEquals($this->admin->id, session('impersonator_id'));
    }

    public function test_niet_admin_kan_niet_impersoneren(): void
    {
        $other = $this->makeUser('other@example.test');

        $this->as($other)
            ->post(route('users.impersonate', $this->user))
            ->assertForbidden();

        $this->assertAuthenticatedAs($other);
    }

    public function test_admin_kan_geen_andere_admin_overnemen(): void
    {
        $otherAdmin = $this->makeUser('admin2@example.test', isAdmin: true);

        $this->as($this->admin)
            ->post(route('users.impersonate', $otherAdmin))
            ->assertForbidden();
    }

    public function test_stoppen_keert_terug_naar_admin(): void
    {
        $this->as($this->admin)->post(route('users.impersonate', $this->user));
        $this->assertAuthenticatedAs($this->user);

        $response = $this->post(route('impersonation.stop'));

        $response->assertRedirect(route('users.index'));
        $this->assertAuthenticatedAs($this->admin);
        $this->assertNull(session('impersonator_id'));
    }

    public function test_stoppen_zonder_impersonatie_geeft_403(): void
    {
        $this->as($this->user)
            ->post(route('impersonation.stop'))
            ->assertForbidden();
    }
}
