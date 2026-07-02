<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Standaard een goedgekeurd account; de DB-default is 'pending',
            // wat login in tests zou blokkeren. Gebruik ->pending() voor dat geval.
            'status' => \App\Models\User::STATUS_APPROVED,
            'approved_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** Account dat nog op goedkeuring wacht. */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => \App\Models\User::STATUS_PENDING,
            'approved_at' => null,
        ]);
    }

    /** Account met MFA actief (combineer met sessie ['mfa_verified' => true]). */
    public function mfaEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'mfa_enabled'      => true,
            'mfa_confirmed_at' => now(),
        ]);
    }
}
