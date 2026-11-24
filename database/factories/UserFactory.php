<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name'              => fake()->firstName(),
            'last_name'               => fake()->lastName(),
            'phone_number'            => '09' . fake()->unique()->numerify('#########'),
            'phone_verified_at'       => now(),
            'registration_unverified' => false,
            'email'                   => fake()->unique()->safeEmail(),
            'email_verified_at'       => now(),
            'password'                => static::$password ??= Hash::make('password123'),
            'role'                    => 'resident',
            'status'                  => 'active',
            'remember_token'          => Str::random(10),
        ];
    }

    public function nurse(): static
    {
        return $this->state(fn () => [
            'role'   => 'nurse',
            'status' => 'active',
        ]);
    }

    public function bhw(): static
    {
        return $this->state(fn () => [
            'role'   => 'bhw',
            'status' => 'active',
        ]);
    }

    public function resident(): static
    {
        return $this->state(fn () => [
            'role'   => 'resident',
            'status' => 'active',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => 'inactive',
        ]);
    }

    public function unverifiedRegistration(): static
    {
        return $this->state(fn () => [
            'status'                  => 'pending',
            'registration_unverified' => true,
            'phone_verified_at'       => null,
        ]);
    }

    public function walkin(): static
    {
        return $this->state(fn () => [
            'phone_number' => 'WALKIN-' . fake()->unique()->numerify('##########-####'),
            'role'         => 'resident',
            'status'       => 'active',
        ]);
    }
}
