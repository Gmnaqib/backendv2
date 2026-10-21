<?php

namespace Tests\Feature;

use App\Models\AktivasiPengajuan;
use App\Models\DaftarPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DaftarPengajuanAksesTest extends TestCase
{
    use RefreshDatabase;

    private function pengajuanMilik(User $pemilik): DaftarPengajuan
    {
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

        return DaftarPengajuan::create([
            'id_aktivasi' => $aktivasi->id,
            'user_id' => $pemilik->id,
            'date' => now(),
            'surat_pengajuan' => '',
        ]);
    }

    public function test_user_lain_tidak_bisa_menghapus_pengajuan_orang(): void
    {
        $pengajuan = $this->pengajuanMilik(User::factory()->create());

        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson('/api/daftar-pengajuan/' . $pengajuan->id)
            ->assertStatus(403);

        $this->assertSame(1, DaftarPengajuan::count());
    }

    public function test_user_lain_tidak_bisa_melihat_pengajuan_orang(): void
    {
        $pengajuan = $this->pengajuanMilik(User::factory()->create());

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/daftar-pengajuan/' . $pengajuan->id)
            ->assertStatus(403);
    }

    public function test_pemilik_boleh_menghapus_pengajuannya(): void
    {
        $pemilik = User::factory()->create();

        $pengajuan = $this->pengajuanMilik($pemilik);

        Sanctum::actingAs($pemilik);

        $this->deleteJson('/api/daftar-pengajuan/' . $pengajuan->id)->assertOk();

        $this->assertSame(0, DaftarPengajuan::count());
    }

    public function test_admin_boleh_menghapus_pengajuan_siapa_pun(): void
    {
        $pengajuan = $this->pengajuanMilik(User::factory()->create());

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->deleteJson('/api/daftar-pengajuan/' . $pengajuan->id)->assertOk();

        $this->assertSame(0, DaftarPengajuan::count());
    }
}
