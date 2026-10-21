<?php

namespace Tests\Feature;

use App\Models\AktivasiPengajuan;
use App\Models\Jabatan;
use App\Models\Pengajuan;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AktivasiSemesterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $jabatan = Jabatan::create(['nama' => 'Direktur']);

        $unit = UnitType::create(['nama' => 'Perlengkapan']);

        return User::create([
            'nip' => '123',
            'username' => 'admin',
            'nama' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'rahasia',
            'jenis_kelamin' => 'Pria',
            'jabatan_id' => $jabatan->id,
            'unit_id' => $unit->id,
            'role' => 'admin',
        ]);
    }

    private function katalog(): void
    {
        Pengajuan::insert([
            ['tipe' => 'kelas', 'semester' => 'ganjil', 'ujian' => 'Default'],
            ['tipe' => 'kelas', 'semester' => 'genap', 'ujian' => 'Default'],
            ['tipe' => 'ujian', 'semester' => 'ganjil', 'ujian' => 'uts'],
            ['tipe' => 'ujian', 'semester' => 'ganjil', 'ujian' => 'uas'],
        ]);
    }

    public function test_semester_yang_dikirim_mengoreksi_id_pengajuan_yang_salah(): void
    {
        Sanctum::actingAs($this->admin());

        $this->katalog();

        $genap = Pengajuan::where('tipe', 'kelas')->where('semester', 'genap')->first();
        $ganjil = Pengajuan::where('tipe', 'kelas')->where('semester', 'ganjil')->first();

        $response = $this->postJson('/api/aktivasi-pengajuan', [
            'id_pengajuan' => $genap->id,
            'semester' => 'ganjil',
            'aktif_mulai' => now()->toDateString(),
            'aktif_selesai' => now()->addDays(7)->toDateString(),
            'tipe' => 'rutin',
            'tahun_akademik' => '2025/2026',
        ]);

        $response->assertCreated();

        $this->assertSame(
            $ganjil->id,
            AktivasiPengajuan::first()->id_pengajuan,
            'semester yang dikirim harus menang atas id_pengajuan yang keliru'
        );
    }

    public function test_tanpa_semester_id_pengajuan_dipakai_apa_adanya(): void
    {
        Sanctum::actingAs($this->admin());

        $this->katalog();

        $genap = Pengajuan::where('tipe', 'kelas')->where('semester', 'genap')->first();

        $this->postJson('/api/aktivasi-pengajuan', [
            'id_pengajuan' => $genap->id,
            'aktif_mulai' => now()->toDateString(),
            'aktif_selesai' => now()->addDays(7)->toDateString(),
            'tipe' => 'rutin',
            'tahun_akademik' => '2025/2026',
        ])->assertCreated();

        $this->assertSame($genap->id, AktivasiPengajuan::first()->id_pengajuan);
    }

    public function test_kombinasi_semester_dan_ujian_yang_tidak_ada_ditolak(): void
    {
        Sanctum::actingAs($this->admin());

        $this->katalog();

        $uts = Pengajuan::where('tipe', 'ujian')->where('ujian', 'uts')->first();

        $this->postJson('/api/aktivasi-pengajuan', [
            'id_pengajuan' => $uts->id,
            'semester' => 'genap',
            'ujian' => 'uts',
            'aktif_mulai' => now()->toDateString(),
            'aktif_selesai' => now()->addDays(7)->toDateString(),
            'tipe' => 'rutin',
            'tahun_akademik' => '2025/2026',
        ])->assertStatus(422);

        $this->assertSame(0, AktivasiPengajuan::count());
    }
}
