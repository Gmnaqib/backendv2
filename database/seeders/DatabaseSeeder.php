<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            JabatanSeeder::class,
            UnitTypeSeeder::class,
            UserSeeder::class,
            VendorSeeder::class,
            BarangSeeder::class,
            PengajuanSeeder::class,
            AktivasiSeeder::class,
            PengumumanSeeder::class,
        ]);
    }
}
