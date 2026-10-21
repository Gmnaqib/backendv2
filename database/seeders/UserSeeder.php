<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $jabatan = Jabatan::first();
        $unit = UnitType::first();

        User::create([
            'nip' => 'ADM001',
            'username' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
            'jenis_kelamin' => 'Pria',
            'jabatan_id' => $jabatan->id,
            'unit_id' => $unit->id,
            'role' => 'admin',
        ]);

        $jabatan2 = Jabatan::skip(1)->first();
        $unit2 = UnitType::skip(1)->first();

        User::create([
            'nip' => 'USR001',
            'username' => 'budi',
            'email' => 'user@gmail.com',
            'password' => Hash::make('password'),
            'jenis_kelamin' => 'Wanita',
            'jabatan_id' => $jabatan2?->id ?? $jabatan->id,
            'unit_id' => $unit2?->id ?? $unit->id,
            'role' => 'user',
        ]);
    }
}
