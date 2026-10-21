<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pengumuman;

class PengumumanSeeder extends Seeder
{
    public function run(): void
    {

        Pengumuman::create([

            'judul' => 'Pengajuan Dibuka',

            'teks' => 'Pengajuan ATK semester ganjil sudah dibuka',

            'create_at' => now()

        ]);
    }
}
