<?php

namespace Tests\Feature;

use App\Models\AktivasiPengajuan;
use App\Models\Barang;
use App\Models\DaftarPengajuan;
use App\Models\Pengajuan;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Store "array" bawaan test tidak menserialisasi nilai, sehingga objek
 * tersimpan apa adanya dan bug serialisasi tidak pernah kelihatan. Di sini
 * serialisasinya dinyalakan supaya perilakunya sama dengan database/Redis
 * di production, lalu dipastikan permintaan kedua benar-benar dilayani cache.
 */
class CacheTerpakaiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.stores.array.serialize' => true]);
        app('cache')->forgetDriver('array');

        $this->siapkanData();
    }

    private function siapkanData(): void
    {
        Pengajuan::insert([
            ['tipe' => 'kelas', 'semester' => 'ganjil', 'ujian' => 'Default'],
        ]);

        $aktivasi = AktivasiPengajuan::create([
            'id_pengajuan' => Pengajuan::first()->id,
            'aktif_mulai' => now()->subDay(),
            'aktif_selesai' => now()->addDays(7),
            'tipe' => 'rutin',
            'tahun_akademik' => '2026/2027',
        ]);

        $pemilik = User::factory()->create();

        $pengajuan = DaftarPengajuan::create([
            'id_aktivasi' => $aktivasi->id,
            'user_id' => $pemilik->id,
            'date' => now(),
            'surat_pengajuan' => '',
        ]);

        $barang = Barang::create([
            'nama' => 'Spidol warna biru',
            'kategori' => 'atk_kelas',
            'tipe' => 'habis_pakai',
            'unit' => 'Buah',
        ]);

        $pengajuan->barang()->create([
            'id_barang' => $barang->id,
            'jumlah' => 38,
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
        ]);

        DB::table('pengumuman')->insert([
            'judul' => 'Pengajuan ATK Kelas',
            'teks' => 'Isi pengumuman',
            'create_at' => now(),
        ]);

        $induk = UnitType::create(['nama' => 'Fakultas Teknik']);
        UnitType::create(['nama' => 'Teknik Informatika', 'parent_id' => $induk->id]);

        Sanctum::actingAs($pemilik->forceFill(['role' => 'admin']));
    }

    private function jumlahQuery(callable $aksi): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $hasil = $aksi();

        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$hasil, $jumlah];
    }

    public static function endpoint(): array
    {
        return [
            'my-pengajuan' => ['/api/my-pengajuan'],
            'histori' => ['/api/histori'],
            'histori-my' => ['/api/histori-pengajuan/my'],
            'daftar-pengajuan' => ['/api/daftar-pengajuan'],
            'admin daftar-pengajuan' => ['/api/admin/daftar-pengajuan'],
            'admin aktivasi-pengajuan' => ['/api/admin/aktivasi-pengajuan'],
            'pengumuman' => ['/api/pengumuman'],
            'dropdown' => ['/api/dropdown'],
        ];
    }

    #[DataProvider('endpoint')]
    public function test_permintaan_kedua_dilayani_cache_dengan_isi_yang_sama(string $url): void
    {
        [$pertama, $queryPertama] = $this->jumlahQuery(fn () => $this->getJson($url));
        [$kedua, $queryKedua] = $this->jumlahQuery(fn () => $this->getJson($url));

        $pertama->assertOk();
        $kedua->assertOk();

        $this->assertGreaterThan(0, $queryPertama, 'permintaan pertama semestinya menembak database');

        $this->assertSame(
            0,
            $queryKedua,
            "permintaan kedua ke {$url} semestinya dilayani cache tanpa query"
        );

        $this->assertSame(
            $pertama->json(),
            $kedua->json(),
            'isi dari cache harus sama persis dengan hasil query'
        );
    }
}
