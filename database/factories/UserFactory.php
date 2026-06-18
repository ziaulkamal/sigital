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
            // Profil lengkap secara default (NIK 16 digit + HP 62…) agar user terapprove
            // tidak terjebak gate EnsureProfileComplete pada test fitur. Gunakan state
            // needsProfile() untuk menguji alur "lengkapi profil".
            'nik' => fake()->unique()->numerify('################'),
            'phone' => '628'.fake()->numerify('#########'),
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

    /** Akun yang belum melengkapi profil (NIK & HP kosong) — untuk uji gate lengkapi profil. */
    public function needsProfile(): static
    {
        return $this->state(fn (array $attributes) => [
            'nik' => null,
            'phone' => null,
        ]);
    }
}
