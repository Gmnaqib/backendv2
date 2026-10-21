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

class CetakBerkasTest extends TestCase
{
    use RefreshDatabase;

    private function siapkan(): AktivasiPengajuan
    {
        Pengajuan::insert([
            ['tipe' => 'kelas', 'semester' => 'ganjil', 'ujian' => 'Default'],
        ]);

        $aktivasi = AktivasiPengajuan::create([
            'id_pengajuan' => Pengajuan::first()->id,
            'aktif_mulai' => '2026-09-22',
            'aktif_selesai' => '2026-09-27',
            'tipe' => 'rutin',
            'tahun_akademik' => '2026/2027',
        ]);

        $pengajuan = DaftarPengajuan::create([
            'id_aktivasi' => $aktivasi->id,
            'user_id' => User::factory()->create()->id,
            'date' => '2026-09-25',
            'surat_pengajuan' => '',
        ]);

        // Dibuat pada 2026, sementara periodenya tahun akademik 2026/2027.
        $pengajuan->forceFill(['created_at' => '2026-09-25 09:04:56'])->save();

        $barang = Barang::create([
            'nama' => 'Spidol warna biru',
            'kategori' => 'atk_kelas',
            'tipe' => 'habis_pakai',
            'unit' => 'Buah',
            'vendor_id' => null,
            'harga' => null,
        ]);

        $pengajuan->barang()->create([
            'id_barang' => $barang->id,
            'jumlah' => 38,
            'jumlah_disetujui' => 0,
            'status' => false,
        ]);

        return $aktivasi;
    }

    public function test_tahun_akhir_periode_akademik_tetap_menampilkan_data(): void
    {
        $aktivasi = $this->siapkan();

        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->getJson('/api/admin/cetak-berkas?' . http_build_query([
            'tahun' => '2027',
            'id_aktivasi' => $aktivasi->id,
        ]));

        $response->assertOk();

        $this->assertCount(
            1,
            $response->json('data.barang_pengajuan'),
            'periode 2026/2027 dikirim sebagai tahun 2027 walau baris dibuat pada 2026'
        );

        $this->assertSame(
            'Spidol warna biru',
            $response->json('data.barang_pengajuan.0.nama_barang')
        );
    }

    public function test_aktivasi_lain_tidak_ikut_terbawa(): void
    {
        $this->siapkan();

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/admin/cetak-berkas?' . http_build_query([
            'tahun' => '2027',
            'id_aktivasi' => 999999,
        ]))->assertOk()->assertJsonCount(0, 'data.barang_pengajuan');
    }
}
