<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;

class BarangSeeder extends Seeder
{
    public function run(): void
    {
        Barang::insert([

            [
                'nama' => 'Pulpen',
                'kategori' => 'atk_tahunan',
                'tipe' => 'habis_pakai',
                'unit' => 'pcs',
                'harga' => 3000,
                'vendor_id' => 1
            ],

            [
                'nama' => 'Pensil',
                'kategori' => 'atk_tahunan',
                'tipe' => 'habis_pakai',
                'unit' => 'pcs',
                'harga' => 2500,
                'vendor_id' => 1
            ],

            [
                'nama' => 'Kertas A4',
                'kategori' => 'atk_tahunan',
                'tipe' => 'habis_pakai',
                'unit' => 'rim',
                'harga' => 65000,
                'vendor_id' => 4
            ],

            [
                'nama' => 'Spidol Whiteboard',
                'kategori' => 'atk_kelas',
                'tipe' => 'habis_pakai',
                'unit' => 'pcs',
                'harga' => 12000,
                'vendor_id' => 3
            ],

            [
                'nama' => 'Printer',
                'kategori' => 'atk_kelas',
                'tipe' => 'tidak_habis_pakai',
                'unit' => 'unit',
                'harga' => 2500000,
                'vendor_id' => 2
            ]

        ]);
    }
}
