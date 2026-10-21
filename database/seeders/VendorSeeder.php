<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vendor;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        Vendor::insert([
            [
                'nama' => 'Gramedia',
                'kontak' => '081234567890'
            ],
            [
                'nama' => 'Epson',
                'kontak' => '082345678901'
            ],
            [
                'nama' => 'Kenko',
                'kontak' => '083456789012'
            ],
            [
                'nama' => 'Standard Stationery',
                'kontak' => '084567890123'
            ]
        ]);
    }
}