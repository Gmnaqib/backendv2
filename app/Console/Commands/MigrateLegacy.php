<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

#[Signature('app:migrate-legacy {--dry-run : Jalankan lalu batalkan, tidak ada yang tersimpan} {--force : Lanjut walau tabel tujuan tidak kosong}')]
#[Description('Pindahkan data dari database lama (koneksi legacy) ke skema v2')]
class MigrateLegacy extends Command
{
    private const TARGETS = [
        'unit_types',
        'jabatans',
        'users',
        'barang',
        'pengajuan',
        'aktivasi_pengajuan',
        'daftar_pengajuan',
        'barang_pengajuan',
        'barang_pengajuan_lainnya',
        'pengumuman',
    ];

    /** Offset id untuk tabel admin lama supaya tidak bentrok dengan users lama. */
    private const ADMIN_ID_OFFSET = 900000;

    /** Induk buatan untuk baris yatim, supaya tidak ada data lama yang dibuang. */
    private const PLACEHOLDER_USER_ID = 999999;
    private const PLACEHOLDER_AKTIVASI_ID = 999999;

    private const JENIS_KELAMIN = [
        'laki-laki' => 'Pria',
        'perempuan' => 'Wanita',
    ];

    private const BARANG_KATEGORI = [
        'atk tahunan' => 'atk_tahunan',
        'atk ujian'   => 'atk_ujian',
        'atk kelas'   => 'atk_kelas',
        'non rutin'   => 'atk_tahunan',
        'lain lain'   => 'atk_tahunan',
    ];

    private const BARANG_TIPE = [
        'habis pakai'       => 'habis_pakai',
        'tidak habis pakai' => 'tidak_habis_pakai',
    ];

    private const PENGAJUAN_TIPE = [
        'kelas'     => 'kelas',
        'tahunan'   => 'tahunan',
        'ujian'     => 'ujian',
        'non rutin' => 'nonrutin',
        ''          => 'nonrutin',
    ];

    private const PENGAJUAN_SEMESTER = [
        'ganjil'  => 'ganjil',
        'genap'   => 'genap',
        'default' => 'tahunan',
    ];

    private const PENGAJUAN_UJIAN = [
        'uts'     => 'uts',
        'uas'     => 'uas',
        'default' => 'Default',
    ];

    private const AKTIVASI_TIPE = [
        'rutin'     => 'rutin',
        'non rutin' => 'nonrutin',
    ];

    /** @var array<int,string> */
    private array $warnings = [];

    /** @var array<string,int> peta id fakultas/prodi lama -> id unit_types baru */
    private array $unitMap = [];

    /** @var array<string,int> peta nama jabatan -> id jabatans baru */
    private array $jabatanMap = [];

    /** @var array<int,int> id users lama -> id users baru */
    private array $userMap = [];

    private array $stats = [];

    public function handle(): int
    {
        $legacy = DB::connection('legacy');

        try {
            $legacyName = $legacy->getDatabaseName();
            $legacy->getPdo();
        } catch (Throwable $e) {
            $this->error('Tidak bisa terhubung ke koneksi "legacy". Cek LEGACY_DB_* di .env.');
            $this->line($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->line("Sumber  : {$legacyName} (legacy)");
        $this->line('Tujuan  : ' . DB::connection()->getDatabaseName() . ' (' . DB::connection()->getName() . ')');
        $this->newLine();

        if (! $this->guardTargetsEmpty()) {
            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');

        // Data lama memakai id 0 (mis. pengajuan#0). Tanpa ini MySQL menukarnya
        // dengan nilai auto-increment berikutnya, lalu baris id 1 ikut bentrok.
        DB::statement("SET SESSION sql_mode = CONCAT(@@SESSION.sql_mode, ',NO_AUTO_VALUE_ON_ZERO')");

        DB::beginTransaction();
        Schema::disableForeignKeyConstraints();

        try {
            $this->migrateUnitTypes($legacy);
            $this->migrateJabatans($legacy);
            $this->migrateUsers($legacy);
            $this->migrateBarang($legacy);
            $this->migratePengajuan($legacy);
            $this->migrateAktivasi($legacy);
            $this->migrateDaftarPengajuan($legacy);
            $this->migrateBarangPengajuan($legacy);
            $this->migrateBarangPengajuanLainnya($legacy);
            $this->migratePengumuman($legacy);
        } catch (Throwable $e) {
            Schema::enableForeignKeyConstraints();
            DB::rollBack();

            $this->newLine();
            $this->error('Gagal: ' . $e->getMessage());
            $this->line('Semua perubahan dibatalkan. Database tujuan tidak berubah.');

            return self::FAILURE;
        }

        Schema::enableForeignKeyConstraints();

        $this->renderReport();

        if ($dryRun) {
            DB::rollBack();
            $this->newLine();
            $this->warn('DRY RUN - semua perubahan dibatalkan, tidak ada yang tersimpan.');

            return self::SUCCESS;
        }

        DB::commit();

        $this->newLine();
        $this->info('Migrasi selesai dan tersimpan.');

        return self::SUCCESS;
    }

    private function guardTargetsEmpty(): bool
    {
        $isi = [];

        foreach (self::TARGETS as $table) {
            $count = DB::table($table)->count();

            if ($count > 0) {
                $isi[] = "{$table} ({$count})";
            }
        }

        if ($isi === []) {
            return true;
        }

        $this->warn('Tabel tujuan sudah berisi data: ' . implode(', ', $isi));

        if ($this->option('force')) {
            $this->warn('Lanjut karena --force. Data bisa terduplikasi.');

            return true;
        }

        $this->error('Jalankan app:db-clean dulu, atau pakai --force kalau memang disengaja.');

        return false;
    }

    private function map(array $table, ?string $key, string $field, string $fallback): string
    {
        $key = (string) $key;

        if (array_key_exists($key, $table)) {
            return $table[$key];
        }

        $this->warnings[] = "{$field}: nilai \"{$key}\" tidak dikenal, dipakai \"{$fallback}\"";

        return $fallback;
    }

    private function migrateUnitTypes($legacy): void
    {
        $now = now();
        $id = 1;

        foreach ($legacy->table('fakultas')->orderBy('id')->get() as $f) {
            DB::table('unit_types')->insert([
                'id'         => $id,
                'nama'       => $f->nama,
                'parent_id'  => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->unitMap["f:{$f->id}"] = $id;
            $id++;
        }

        foreach ($legacy->table('prodi')->orderBy('id')->get() as $p) {
            $parent = $this->unitMap["f:{$p->id_fakultas}"] ?? null;

            if ($parent === null) {
                $this->warnings[] = "prodi #{$p->id} menunjuk fakultas #{$p->id_fakultas} yang tidak ada, parent dikosongkan";
            }

            DB::table('unit_types')->insert([
                'id'         => $id,
                'nama'       => $p->nama,
                'parent_id'  => $parent,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->unitMap["p:{$p->id}"] = $id;
            $id++;
        }

        $this->stats['unit_types'] = $id - 1;
    }

    private function migrateJabatans($legacy): void
    {
        $now = now();

        $namaJabatan = $legacy->table('users')
            ->whereNotNull('jabatan')
            ->where('jabatan', '!=', '')
            ->distinct()
            ->pluck('jabatan')
            ->all();

        sort($namaJabatan);

        // Jabatan cadangan untuk user lama yang kolom jabatannya kosong.
        array_unshift($namaJabatan, 'Tidak Diketahui');

        $id = 1;

        foreach (array_unique($namaJabatan) as $nama) {
            DB::table('jabatans')->insert([
                'id'         => $id,
                'nama'       => $nama,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->jabatanMap[$nama] = $id;
            $id++;
        }

        $this->stats['jabatans'] = $id - 1;
    }

    private function migrateUsers($legacy): void
    {
        $now = now();
        $unitCadangan = reset($this->unitMap) ?: null;
        $count = 0;

        foreach ($legacy->table('users')->orderBy('id')->get() as $u) {
            $unit = $this->unitMap["p:{$u->id_prodi}"] ?? $unitCadangan;

            if (! isset($this->unitMap["p:{$u->id_prodi}"])) {
                $this->warnings[] = "user #{$u->id} menunjuk prodi #{$u->id_prodi} yang tidak ada, unit dialihkan";
            }

            $jabatan = ($u->jabatan !== null && $u->jabatan !== '') ? $u->jabatan : 'Tidak Diketahui';

            DB::table('users')->insert([
                'id'            => $u->id,
                'nip'           => $u->nip !== null ? (string) $u->nip : null,
                'nama'          => $u->nama,
                'username'      => $u->username,
                'email'         => $u->email,
                'password'      => $u->password,
                'jenis_kelamin' => $this->map(self::JENIS_KELAMIN, $u->jenis_kelamin, 'users.jenis_kelamin', 'Pria'),
                'foto'          => $u->foto_profile,
                'jabatan_id'    => $this->jabatanMap[$jabatan] ?? 1,
                'unit_id'       => $unit,
                'role'          => $u->kategori === 'user' ? 'user' : 'admin',
                'created_at'    => $u->created_at ?? $now,
                'updated_at'    => $u->updated_at ?? $now,
            ]);

            $this->userMap[$u->id] = $u->id;
            $count++;
        }

        // Tabel admin lama tidak punya email/prodi/jenis kelamin, jadi diisi nilai sintetis.
        foreach ($legacy->table('admin')->orderBy('id')->get() as $a) {
            $newId = self::ADMIN_ID_OFFSET + $a->id;

            DB::table('users')->insert([
                'id'            => $newId,
                'nip'           => null,
                'nama'          => $a->nama,
                'username'      => $a->username,
                'email'         => $a->username . '@unikom.ac.id',
                'password'      => $a->password,
                'jenis_kelamin' => 'Pria',
                'foto'          => null,
                'jabatan_id'    => 1,
                'unit_id'       => $unitCadangan,
                'role'          => 'admin',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            $this->warnings[] = "admin #{$a->id} ({$a->username}) dibuat sebagai user id {$newId}, email & jenis kelamin diisi nilai sementara";
            $count++;
        }

        $this->stats['users'] = $count;
    }

    private function migrateBarang($legacy): void
    {
        $now = now();
        $count = 0;

        foreach ($legacy->table('barang')->orderBy('id_barang')->get() as $b) {
            DB::table('barang')->insert([
                'id'   => $b->id_barang,
                'nama' => $b->nama,
                // Sengaja disilang: 'jenis' lama = kategori v2, 'kategori' lama = tipe v2.
                'kategori'   => $this->map(self::BARANG_KATEGORI, $b->jenis, 'barang.jenis', 'atk_tahunan'),
                'tipe'       => $this->map(self::BARANG_TIPE, $b->kategori, 'barang.kategori', 'habis_pakai'),
                'unit'       => $b->satuan,
                'vendor_id'  => null,
                'harga'      => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $count++;
        }

        $this->stats['barang'] = $count;

        $count += $this->buatBarangPengganti($legacy);
    }

    /**
     * Sebagian barang sudah dihapus dari master data lama, tapi riwayat
     * pengajuannya masih ada. Barang pengganti dibuat supaya baris itu tetap
     * terbawa dan foreign key-nya tetap sah.
     */
    private function buatBarangPengganti($legacy): int
    {
        $now = now();
        $ada = DB::table('barang')->pluck('id')->flip();

        $dipakai = $legacy->table('barang_pengajuan')
            ->distinct()
            ->pluck('id_barang')
            ->all();

        $hilang = array_values(array_filter($dipakai, fn ($id) => ! isset($ada[$id])));
        sort($hilang);

        foreach ($hilang as $id) {
            DB::table('barang')->insert([
                'id'         => $id,
                'nama'       => "Barang dihapus #{$id}",
                'kategori'   => 'atk_tahunan',
                'tipe'       => 'habis_pakai',
                'unit'       => '-',
                'vendor_id'  => null,
                'harga'      => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($hilang !== []) {
            $this->stats['barang (pengganti)'] = count($hilang);
            $this->warnings[] = count($hilang) . ' barang pengganti dibuat untuk id yang sudah dihapus di sistem lama: ' . implode(', ', $hilang);
        }

        return count($hilang);
    }

    private function migratePengajuan($legacy): void
    {
        $count = 0;

        foreach ($legacy->table('pengajuan')->orderBy('id_pengajuan')->get() as $p) {
            DB::table('pengajuan')->insert([
                'id'       => $p->id_pengajuan,
                'tipe'     => $this->map(self::PENGAJUAN_TIPE, $p->jenis, 'pengajuan.jenis', 'nonrutin'),
                'semester' => $this->map(self::PENGAJUAN_SEMESTER, $p->semester, 'pengajuan.semester', 'tahunan'),
                'ujian'    => $this->map(self::PENGAJUAN_UJIAN, $p->ujian, 'pengajuan.ujian', 'Default'),
            ]);

            $count++;
        }

        $this->stats['pengajuan'] = $count;
    }

    private function migrateAktivasi($legacy): void
    {
        $now = now();
        $count = 0;

        foreach ($legacy->table('aktivasi_pengajuan')->orderBy('id_aktivasi')->get() as $a) {
            DB::table('aktivasi_pengajuan')->insert([
                'id'             => $a->id_aktivasi,
                'id_pengajuan'   => $a->id_pengajuan,
                'aktif_mulai'    => $a->aktif_mulai,
                'aktif_selesai'  => $a->aktif_selesai,
                'tipe'           => $this->map(self::AKTIVASI_TIPE, $a->jenis, 'aktivasi.jenis', 'rutin'),
                'tahun_akademik' => $a->tahun_akademik ?? '',
                'created_at'     => $a->created_at ?? $now,
                'updated_at'     => $a->updated_at ?? $now,
            ]);

            $count++;
        }

        $this->stats['aktivasi_pengajuan'] = $count;
    }

    private function migrateDaftarPengajuan($legacy): void
    {
        $now = now();
        $count = 0;
        $lewat = 0;

        foreach ($legacy->table('daftar_pengajuan')->orderBy('id_daftar_pengajuan')->get() as $d) {
            $user = $this->userMap[$d->id_pengaju] ?? null;

            if ($user === null) {
                $user = $this->pastikanUserPengganti();
                $this->warnings[] = "daftar_pengajuan menunjuk pengaju yang tidak ada di users, dialihkan ke user pengganti";
                $lewat++;
            }

            DB::table('daftar_pengajuan')->insert([
                'id'              => $d->id_daftar_pengajuan,
                'id_aktivasi'     => $d->id_aktivasi,
                'user_id'         => $user,
                'date'            => $d->tanggal,
                'surat_pengajuan' => $d->surat_permohonan ?? '',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            $count++;
        }

        $this->stats['daftar_pengajuan'] = $count;

        if ($lewat > 0) {
            $this->stats['daftar_pengajuan (pengaju hilang)'] = $lewat;
        }

        $this->buatDaftarPengganti($legacy);
    }

    /** User buatan untuk menampung baris yang induknya sudah tidak ada. */
    private function pastikanUserPengganti(): int
    {
        if (DB::table('users')->where('id', self::PLACEHOLDER_USER_ID)->exists()) {
            return self::PLACEHOLDER_USER_ID;
        }

        $now = now();

        DB::table('users')->insert([
            'id'            => self::PLACEHOLDER_USER_ID,
            'nip'           => null,
            'nama'          => 'Pengguna Tidak Dikenal (data lama)',
            'username'      => 'pengguna-tidak-dikenal',
            'email'         => 'pengguna-tidak-dikenal@unikom.ac.id',
            'password'      => '',
            'jenis_kelamin' => 'Pria',
            'foto'          => null,
            'jabatan_id'    => 1,
            'unit_id'       => DB::table('unit_types')->min('id'),
            'role'          => 'user',
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $this->stats['users (pengganti)'] = 1;

        return self::PLACEHOLDER_USER_ID;
    }

    /**
     * Sebagian barang_pengajuan menunjuk daftar_pengajuan yang sudah tidak ada.
     * Daftar pengganti dibuat supaya baris itu tetap terbawa.
     */
    private function buatDaftarPengganti($legacy): void
    {
        $ada = DB::table('daftar_pengajuan')->pluck('id')->flip();

        $dipakai = array_merge(
            $legacy->table('barang_pengajuan')->distinct()->pluck('id_daftar_pengajuan')->all(),
            $legacy->table('barang_pengajuan_lainnya')->distinct()->pluck('id_daftar_pengajuan')->all(),
        );

        $hilang = array_values(array_unique(array_filter($dipakai, fn ($id) => ! isset($ada[$id]))));
        sort($hilang);

        if ($hilang === []) {
            return;
        }

        $now = now();
        $aktivasi = $this->pastikanAktivasiPengganti();
        $user = $this->pastikanUserPengganti();

        foreach ($hilang as $id) {
            DB::table('daftar_pengajuan')->insert([
                'id'              => $id,
                'id_aktivasi'     => $aktivasi,
                'user_id'         => $user,
                'date'            => $now,
                'surat_pengajuan' => '',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        $this->stats['daftar_pengajuan (pengganti)'] = count($hilang);
        $this->warnings[] = count($hilang) . ' daftar_pengajuan pengganti dibuat untuk id yang sudah tidak ada di sistem lama: ' . implode(', ', $hilang);
    }

    private function pastikanAktivasiPengganti(): int
    {
        if (DB::table('aktivasi_pengajuan')->where('id', self::PLACEHOLDER_AKTIVASI_ID)->exists()) {
            return self::PLACEHOLDER_AKTIVASI_ID;
        }

        $now = now();

        DB::table('aktivasi_pengajuan')->insert([
            'id'             => self::PLACEHOLDER_AKTIVASI_ID,
            'id_pengajuan'   => DB::table('pengajuan')->min('id'),
            'aktif_mulai'    => $now->toDateString(),
            'aktif_selesai'  => $now->toDateString(),
            'tipe'           => 'nonrutin',
            'tahun_akademik' => 'data lama',
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        $this->stats['aktivasi_pengajuan (pengganti)'] = 1;

        return self::PLACEHOLDER_AKTIVASI_ID;
    }

    private function migrateBarangPengajuan($legacy): void
    {
        $now = now();
        $adaDaftar = DB::table('daftar_pengajuan')->pluck('id')->flip();
        $adaBarang = DB::table('barang')->pluck('id')->flip();
        $count = 0;
        $lewat = 0;

        foreach ($legacy->table('barang_pengajuan')->orderBy('id_barang_pengajuan')->cursor() as $bp) {
            if (! isset($adaDaftar[$bp->id_daftar_pengajuan]) || ! isset($adaBarang[$bp->id_barang])) {
                $lewat++;

                continue;
            }

            DB::table('barang_pengajuan')->insert([
                'id'                  => $bp->id_barang_pengajuan,
                'daftar_pengajuan_id' => $bp->id_daftar_pengajuan,
                'id_barang'           => $bp->id_barang,
                'jumlah'              => $bp->jumlah,
                'jumlah_disetujui'    => $bp->jumlah_disetujui,
                'status'              => (bool) $bp->status,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);

            $count++;
        }

        $this->stats['barang_pengajuan'] = $count;

        if ($lewat > 0) {
            $this->stats['barang_pengajuan (dilewati)'] = $lewat;
            $this->warnings[] = "barang_pengajuan: {$lewat} baris dilewati karena daftar_pengajuan atau barang-nya tidak ada";
        }
    }

    private function migrateBarangPengajuanLainnya($legacy): void
    {
        $adaDaftar = DB::table('daftar_pengajuan')->pluck('id')->flip();
        $count = 0;
        $lewat = 0;

        foreach ($legacy->table('barang_pengajuan_lainnya')->orderBy('id_barang_lainnya')->cursor() as $bl) {
            if (! isset($adaDaftar[$bl->id_daftar_pengajuan])) {
                $lewat++;

                continue;
            }

            DB::table('barang_pengajuan_lainnya')->insert([
                'id'                  => $bl->id_barang_lainnya,
                'daftar_pengajuan_id' => $bl->id_daftar_pengajuan,
                'nama'                => $bl->nama_barang,
                'jumlah'              => $bl->jumlah,
                'jumlah_disetujui'    => $bl->jumlah_disetujui,
                'kategori'            => $this->map(self::BARANG_TIPE, $bl->kategori, 'lainnya.kategori', 'habis_pakai'),
                'satuan'              => $bl->satuan,
                'status'              => (bool) $bl->status,
                'bukti_foto'          => null,
                'alasan'              => $bl->keterangan,
            ]);

            $count++;
        }

        $this->stats['barang_pengajuan_lainnya'] = $count;

        if ($lewat > 0) {
            $this->stats['barang_pengajuan_lainnya (dilewati)'] = $lewat;
            $this->warnings[] = "barang_pengajuan_lainnya: {$lewat} baris dilewati karena daftar_pengajuan-nya tidak ada";
        }
    }

    private function migratePengumuman($legacy): void
    {
        $count = 0;

        foreach ($legacy->table('pengumuman')->orderBy('id_pengumuman')->get() as $p) {
            DB::table('pengumuman')->insert([
                'id'        => $p->id_pengumuman,
                'judul'     => $p->judul,
                'teks'      => $p->isi,
                'gambar'    => $p->foto !== '' ? $p->foto : null,
                'create_at' => $p->tanggal,
            ]);

            $count++;
        }

        $this->stats['pengumuman'] = $count;
    }

    private function renderReport(): void
    {
        $rows = [];

        foreach ($this->stats as $table => $count) {
            $rows[] = [$table, number_format($count)];
        }

        $this->table(['Tabel', 'Baris masuk'], $rows);

        if ($this->warnings === []) {
            return;
        }

        $this->newLine();
        $this->warn('Catatan (' . count($this->warnings) . '):');

        $ringkas = array_count_values($this->warnings);
        $n = 0;

        foreach ($ringkas as $pesan => $jumlah) {
            if ($n++ >= 25) {
                $this->line('  ... dan ' . (count($ringkas) - 25) . ' catatan lain');

                break;
            }

            $this->line('  - ' . $pesan . ($jumlah > 1 ? " (x{$jumlah})" : ''));
        }
    }
}
