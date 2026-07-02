<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Nieuwe aanvragen worden NIET automatisch ingelogd: ze staan op 'pending'
        // en moeten eerst door een admin worden goedgekeurd.
        $this->assertGuest();
        $response->assertRedirect(route('register.pending'));

        $this->assertDatabaseHas('users', [
            'email'  => 'test@example.com',
            'status' => \App\Models\User::STATUS_PENDING,
        ]);
    }
}
