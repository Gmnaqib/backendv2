<?php

namespace Database\Factories;

use App\Models\Jabatan;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'nip' => fake()->unique()->numerify('##########'),
            'username' => fake()->unique()->userName(),
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'jenis_kelamin' => fake()->randomElement(['Pria', 'Wanita']),
            'foto' => null,
            'jabatan_id' => fn () => Jabatan::create(['nama' => 'Dosen'])->id,
            'unit_id' => fn () => UnitType::create(['nama' => 'Umum'])->id,
            'role' => 'user',
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function jabatan(string $nama): static
    {
        return $this->state(fn (array $attributes) => [
            'jabatan_id' => fn () => Jabatan::create(['nama' => $nama])->id,
        ]);
    }
}
