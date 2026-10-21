<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use Illuminate\Database\Seeder;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        Jabatan::insert([
            ['nama' => 'Admin'],
            ['nama' => 'Kepala Lab'],
            ['nama' => 'Staff'],
            ['nama' => 'Dosen']
        ]);
    }
}
