<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UnitType;

class UnitTypeSeeder extends Seeder
{
    public function run(): void
    {
        $fakultas = UnitType::create([
            'nama' => 'Fakultas',
            'parent_id' => null
        ]);

        $fakultasTeknik = UnitType::create([
            'nama' => 'Fakultas Teknik & Ilmu Komputer',
            'parent_id' => $fakultas->id
        ]);

        UnitType::create([
            'nama' => 'Prodi Teknik Informatika',
            'parent_id' => $fakultasTeknik->id
        ]);
    }
}
