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
            'username' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
            'level_akses' => 'admin_ops',
            'nama_sekolah' => null,
            'jabatan' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user is a superadmin.
     */
    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'level_akses' => 'superadmin',
            'nama_sekolah' => null,
            'jabatan' => null,
        ]);
    }

    /**
     * Indicate that the user is an Admin OPS.
     */
    public function adminOps(): static
    {
        return $this->state(fn (array $attributes) => [
            'level_akses' => 'admin_ops',
            'jabatan' => 'Operator Sekolah',
        ]);
    }

    /**
     * Indicate that the user is an Admin BOSP.
     */
    public function adminBosp(): static
    {
        return $this->state(fn (array $attributes) => [
            'level_akses' => 'admin_bosp',
            'jabatan' => 'Admin BOSP',
        ]);
    }
}
