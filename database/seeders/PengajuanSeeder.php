<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pengajuan;
use Illuminate\Support\Facades\DB;

class PengajuanSeeder extends Seeder
{
    public function run(): void
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('pengajuan')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Pengajuan::insert([

            [
                'tipe' => 'kelas',
                'semester' => 'ganjil',
                'ujian' => 'Default'
            ],

            [
                'tipe' => 'kelas',
                'semester' => 'genap',
                'ujian' => 'Default'
            ],

            [
                'tipe' => 'ujian',
                'semester' => 'ganjil',
                'ujian' => 'uts'
            ],

            [
                'tipe' => 'ujian',
                'semester' => 'ganjil',
                'ujian' => 'uas'
            ],

            [
                'tipe' => 'ujian',
                'semester' => 'genap',
                'ujian' => 'uts'
            ],

            [
                'tipe' => 'ujian',
                'semester' => 'genap',
                'ujian' => 'uas'
            ],

            [
                'tipe' => 'tahunan',
                'semester' => 'tahunan',
                'ujian' => 'Default'
            ],

            [
                'tipe' => 'nonrutin',
                'semester' => 'ganjil',
                'ujian' => 'Default'
            ],

            [
                'tipe' => 'nonrutin',
                'semester' => 'genap',
                'ujian' => 'Default'
            ],

        ]);
    }
}