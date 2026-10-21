<?php

namespace Tests\Feature;

use App\Models\AktivasiPengajuan;
use App\Models\DaftarPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreFullPengajuanTest extends TestCase
{
    use RefreshDatabase;

    private function duaPeriodeKelasAktif(): array
    {
        Pengajuan::insert([
            ['tipe' => 'kelas', 'semester' => 'ganjil', 'ujian' => 'Default'],
            ['tipe' => 'kelas', 'semester' => 'genap', 'ujian' => 'Default'],
        ]);

        $ganjil = Pengajuan::where('semester', 'ganjil')->first();
        $genap = Pengajuan::where('semester', 'genap')->first();

        $aktivasiGanjil = AktivasiPengajuan::create([
            'id_pengajuan' => $ganjil->id,
            'aktif_mulai' => now()->subDay(),
            'aktif_selesai' => now()->addDays(7),
            'tipe' => 'rutin',
            'tahun_akademik' => '2025/2026',
        ]);

        $aktivasiGenap = AktivasiPengajuan::create([
            'id_pengajuan' => $genap->id,
            'aktif_mulai' => now()->subDay(),
            'aktif_selesai' => now()->addDays(7),
            'tipe' => 'rutin',
            'tahun_akademik' => '2025/2026',
        ]);

        return [$aktivasiGanjil, $aktivasiGenap];
    }

    public function test_kaprodi_dengan_titik_tanpa_spasi_boleh_mengajukan_kelas(): void
    {
        [$ganjil] = $this->duaPeriodeKelasAktif();

        Sanctum::actingAs(User::factory()->jabatan('Ka.Prodi')->create());

        $this->postJson('/api/daftar-pengajuan/full', [
            'tipe' => 'kelas',
            'semester' => 'ganjil',
        ])->assertOk();

        $this->assertSame($ganjil->id, DaftarPengajuan::first()->id_aktivasi);
    }

    public function test_jabatan_biasa_ditolak_untuk_kelas(): void
    {
        $this->duaPeriodeKelasAktif();

        Sanctum::actingAs(User::factory()->jabatan('Kepala Bagian')->create());

        $this->postJson('/api/daftar-pengajuan/full', [
            'tipe' => 'kelas',
            'semester' => 'ganjil',
        ])->assertStatus(403);

        $this->assertSame(0, DaftarPengajuan::count());
    }

    public function test_barang_lainnya_ditolak_untuk_kelas(): void
    {
        $this->duaPeriodeKelasAktif();

        Sanctum::actingAs(User::factory()->jabatan('Kaprodi')->create());

        $this->postJson('/api/daftar-pengajuan/full', [
            'tipe' => 'kelas',
            'semester' => 'ganjil',
            'barang_lainnya' => [
                [
                    'nama' => 'Spidol merek lain',
                    'jumlah' => 2,
                    'kategori' => 'habis_pakai',
                    'satuan' => 'Buah',
                ],
            ],
        ])->assertStatus(422);

        $this->assertSame(0, DaftarPengajuan::count());
    }

    public function test_barang_lainnya_kosong_tetap_boleh_untuk_kelas(): void
    {
        [$ganjil] = $this->duaPeriodeKelasAktif();

        Sanctum::actingAs(User::factory()->jabatan('Kaprodi')->create());

        $this->postJson('/api/daftar-pengajuan/full', [
            'tipe' => 'kelas',
            'semester' => 'ganjil',
            'barang_lainnya' => [],
        ])->assertOk();

        $this->assertSame($ganjil->id, DaftarPengajuan::first()->id_aktivasi);
    }

    public function test_dua_periode_aktif_tanpa_semester_ditolak_bukan_ditebak(): void
    {
        $this->duaPeriodeKelasAktif();

        Sanctum::actingAs(User::factory()->jabatan('Ketua Program Studi')->create());

        $this->postJson('/api/daftar-pengajuan/full', [
            'tipe' => 'kelas',
        ])->assertStatus(422);

        $this->assertSame(0, DaftarPengajuan::count());
    }
}
