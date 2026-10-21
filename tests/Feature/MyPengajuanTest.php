<?php

namespace Tests\Feature;

use App\Models\AktivasiPengajuan;
use App\Models\Barang;
use App\Models\DaftarPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyPengajuanTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_pengajuan_dengan_barang_tanpa_harga_dan_vendor(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Pengajuan::insert([
            ['tipe' => 'tahunan', 'semester' => 'tahunan', 'ujian' => 'Default'],
        ]);

        $aktivasi = AktivasiPengajuan::create([
            'id_pengajuan' => Pengajuan::first()->id,
            'aktif_mulai' => now()->subDay(),
            'aktif_selesai' => now()->addDays(7),
            'tipe' => 'rutin',
            'tahun_akademik' => '2025/2026',
        ]);

        $pengajuan = DaftarPengajuan::create([
            'id_aktivasi' => $aktivasi->id,
            'user_id' => $user->id,
            'date' => now(),
            'surat_pengajuan' => '',
        ]);

        $barang = Barang::create([
            'nama' => 'Barang dihapus #38',
            'kategori' => 'atk_tahunan',
            'tipe' => 'habis_pakai',
            'unit' => '-',
            'vendor_id' => null,
            'harga' => null,
        ]);

        $pengajuan->barang()->create([
            'id_barang' => $barang->id,
            'jumlah' => 5,
            'jumlah_disetujui' => 0,
            'status' => false,
        ]);

        $pengajuan->barangLainnya()->create([
            'nama' => 'Barang manual',
            'jumlah' => 2,
            'kategori' => 'habis_pakai',
            'satuan' => 'Buah',
            'jumlah_disetujui' => 0,
            'status' => false,
            'vendor_id' => null,
        ]);

        $this->getJson('/api/my-pengajuan')->assertOk();
    }

    public function test_my_pengajuan_kosong(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/my-pengajuan')->assertOk();
    }
}
