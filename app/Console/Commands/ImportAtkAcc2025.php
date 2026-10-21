<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('atk:import-acc-2025
    {file=storage/app/import/rencana_import_atk_2025.csv : Path file rencana import}
    {--apply : Simpan perubahan. Tanpa opsi ini command hanya DRY RUN}')]
#[Description('Masukkan jumlah ACC rektor ATK Tahunan 2025 (aktivasi 26) sesuai Excel logistik')]
class ImportAtkAcc2025 extends Command
{
    private const AKTIVASI_ID = 26;

    /** Total dari Excel ACC rektor, dipakai sebagai pengecekan akhir. */
    private const TOTAL_ACC_TAHUNAN = 24383;
    private const TOTAL_ACC_LAINNYA = 6859;

    private const STATUS_PENDING = 0;

    private array $vendorId = [];
    private array $daftarBaru = [];
    private array $errors = [];
    private array $stats = [];
    private $log;

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $path = base_path($this->argument('file'));

        if (! is_file($path)) {
            $this->error("File rencana tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $rows = $this->bacaCsv($path);

        $this->newLine();
        $this->line('Database : ' . DB::connection()->getDatabaseName());
        $this->line('Rencana  : ' . count($rows) . ' operasi');
        $this->line('Mode     : ' . ($apply ? 'APPLY (perubahan disimpan)' : 'DRY RUN (tidak ada yang disimpan)'));
        $this->newLine();

        if (! $this->cekAwal($rows)) {
            return self::FAILURE;
        }

        if ($apply && ! $this->confirm('Yakin menyimpan perubahan ke database "' . DB::connection()->getDatabaseName() . '"?')) {
            $this->warn('Dibatalkan.');

            return self::FAILURE;
        }

        $logPath = storage_path('logs/atk_import_2025_' . now()->format('Ymd_His') . ($apply ? '' : '_dryrun') . '.csv');
        $this->log = fopen($logPath, 'w');
        fputcsv($this->log, ['op', 'tabel', 'id', 'sebelum', 'sesudah', 'keterangan']);

        DB::beginTransaction();

        try {
            foreach ($rows as $i => $r) {
                $baris = $i + 2;

                match ($r['op']) {
                    'VENDOR_ENSURE' => $this->vendorEnsure($r),
                    'BARANG_UPDATE' => $this->barangUpdate($r, $baris),
                    'DAFTAR_CREATE' => $this->daftarCreate($r),
                    'BP_UPDATE'     => $this->bpUpdate($r, $baris),
                    'BP_INSERT'     => $this->bpInsert($r, $baris),
                    'BPL_UPDATE'    => $this->bplUpdate($r, $baris),
                    'BPL_INSERT'    => $this->bplInsert($r, $baris),
                    default         => $this->errors[] = "Baris {$baris}: op tidak dikenal \"{$r['op']}\"",
                };

                $this->stats[$r['op']] = ($this->stats[$r['op']] ?? 0) + 1;
            }

            if ($this->errors === []) {
                $this->verifikasiAkhir();
            }
        } catch (Throwable $e) {
            DB::rollBack();
            fclose($this->log);
            $this->error('Gagal: ' . $e->getMessage());
            $this->line('Semua perubahan dibatalkan. Database tidak berubah.');

            return self::FAILURE;
        }

        fclose($this->log);
        $this->table(['Operasi', 'Jumlah'], collect($this->stats)->map(fn ($v, $k) => [$k, number_format($v)])->values()->all());

        if ($this->errors !== []) {
            DB::rollBack();
            $this->newLine();
            $this->error('Ditemukan ' . count($this->errors) . ' masalah. Semua perubahan dibatalkan:');

            foreach (array_slice($this->errors, 0, 30) as $err) {
                $this->line('  - ' . $err);
            }

            if (count($this->errors) > 30) {
                $this->line('  ... dan ' . (count($this->errors) - 30) . ' lainnya');
            }

            return self::FAILURE;
        }

        if (! $apply) {
            DB::rollBack();
            $this->newLine();
            $this->info('DRY RUN berhasil, semua pengecekan lolos. Tidak ada yang tersimpan.');
            $this->line("Log rencana perubahan: {$logPath}");
            $this->line('Jalankan ulang dengan --apply untuk menyimpan.');

            return self::SUCCESS;
        }

        DB::commit();

        if (class_exists(\App\Support\PengajuanCache::class)) {
            \App\Support\PengajuanCache::flush();
            $this->line('Cache pengajuan dibersihkan.');
        }

        $this->newLine();
        $this->info('Import selesai dan tersimpan.');
        $this->line("Log perubahan: {$logPath}");

        return self::SUCCESS;
    }

    private function bacaCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        $header = fgetcsv($fh);
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $rows = [];

        while (($line = fgetcsv($fh)) !== false) {
            if ($line === [null]) {
                continue;
            }

            $rows[] = array_combine($header, array_map(fn ($v) => $v === '' ? null : $v, $line));
        }

        fclose($fh);

        return $rows;
    }

    private function cekAwal(array $rows): bool
    {
        $aktivasi = DB::table('aktivasi_pengajuan')
            ->join('pengajuan', 'pengajuan.id', '=', 'aktivasi_pengajuan.id_pengajuan')
            ->where('aktivasi_pengajuan.id', self::AKTIVASI_ID)
            ->select('aktivasi_pengajuan.*', 'pengajuan.tipe as jenis')
            ->first();

        if (! $aktivasi || $aktivasi->jenis !== 'tahunan' || $aktivasi->tahun_akademik !== '2025') {
            $this->error('Aktivasi #' . self::AKTIVASI_ID . ' bukan ATK tahunan 2025. Import dihentikan.');

            return false;
        }

        foreach ($rows as $r) {
            if ($r['op'] === 'DAFTAR_CREATE') {
                $sudah = DB::table('daftar_pengajuan')
                    ->where('id_aktivasi', self::AKTIVASI_ID)
                    ->where('user_id', $r['user_id'])
                    ->exists();

                if ($sudah) {
                    $this->error("User #{$r['user_id']} sudah punya pengajuan di aktivasi " . self::AKTIVASI_ID . '. Data sudah berubah sejak export, buat ulang rencana import.');

                    return false;
                }
            }
        }

        return true;
    }

    private function catat(string $op, string $tabel, $id, $sebelum, $sesudah, ?string $ket): void
    {
        fputcsv($this->log, [$op, $tabel, $id, json_encode($sebelum, JSON_UNESCAPED_UNICODE), json_encode($sesudah, JSON_UNESCAPED_UNICODE), $ket]);
    }

    private function vendorEnsure(array $r): void
    {
        $vendor = DB::table('vendors')->where('nama', $r['nama'])->first();

        if (! $vendor) {
            $id = DB::table('vendors')->insertGetId([
                'nama' => $r['nama'], 'kontak' => '-', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->catat('VENDOR_ENSURE', 'vendors', $id, null, ['nama' => $r['nama']], 'Vendor baru');
            $this->vendorId[$r['nama']] = $id;

            return;
        }

        $this->vendorId[$r['nama']] = $vendor->id;
    }

    private function vendor(?string $nama, int $baris): ?int
    {
        if ($nama === null) {
            return null;
        }

        if (! isset($this->vendorId[$nama])) {
            $this->errors[] = "Baris {$baris}: vendor \"{$nama}\" belum dibuat";

            return null;
        }

        return $this->vendorId[$nama];
    }

    private function daftarId(string $ref, int $baris): ?int
    {
        if (str_starts_with($ref, 'NEW:')) {
            if (! isset($this->daftarBaru[$ref])) {
                $this->errors[] = "Baris {$baris}: daftar baru {$ref} belum dibuat";

                return null;
            }

            return $this->daftarBaru[$ref];
        }

        $ok = DB::table('daftar_pengajuan')->where('id', $ref)->where('id_aktivasi', self::AKTIVASI_ID)->exists();

        if (! $ok) {
            $this->errors[] = "Baris {$baris}: daftar_pengajuan #{$ref} tidak ada di aktivasi " . self::AKTIVASI_ID;

            return null;
        }

        return (int) $ref;
    }

    private function barangUpdate(array $r, int $baris): void
    {
        $b = DB::table('barang')->where('id', $r['id'])->lockForUpdate()->first();

        if (! $b || $b->nama !== $r['expect_nama']) {
            $this->errors[] = "Baris {$baris}: barang #{$r['id']} namanya tidak sesuai (di DB: " . ($b->nama ?? 'tidak ada') . ')';

            return;
        }

        $baru = [
            'nama'      => $r['nama'],
            'unit'      => $r['unit'],
            'harga'     => $r['harga'],
            'vendor_id' => $this->vendor($r['vendor_nama'], $baris),
        ];

        DB::table('barang')->where('id', $b->id)->update($baru + ['updated_at' => now()]);
        $this->catat('BARANG_UPDATE', 'barang', $b->id, ['nama' => $b->nama, 'unit' => $b->unit, 'harga' => $b->harga, 'vendor_id' => $b->vendor_id], $baru, $r['keterangan']);
    }

    private function daftarCreate(array $r): void
    {
        $id = DB::table('daftar_pengajuan')->insertGetId([
            'id_aktivasi'     => self::AKTIVASI_ID,
            'user_id'         => $r['user_id'],
            'date'            => $r['date'],
            'surat_pengajuan' => '',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->daftarBaru[$r['daftar_ref']] = $id;
        $this->catat('DAFTAR_CREATE', 'daftar_pengajuan', $id, null, ['user_id' => $r['user_id'], 'date' => $r['date']], $r['keterangan']);
    }

    private function cekSebelum(object $row, array $r, string $tabel, int $baris): bool
    {
        $sesuai = (int) $row->jumlah === (int) $r['expect_jumlah']
            && (int) $row->jumlah_disetujui === (int) $r['expect_disetujui']
            && (int) $row->status === (int) $r['expect_status'];

        if (! $sesuai) {
            $this->errors[] = "Baris {$baris}: {$tabel} #{$row->id} sudah berubah sejak export "
                . "(jumlah {$row->jumlah}, disetujui {$row->jumlah_disetujui}, status {$row->status})";
        }

        return $sesuai;
    }

    private function bpUpdate(array $r, int $baris): void
    {
        $row = DB::table('barang_pengajuan')
            ->join('daftar_pengajuan', 'daftar_pengajuan.id', '=', 'barang_pengajuan.daftar_pengajuan_id')
            ->where('barang_pengajuan.id', $r['id'])
            ->where('daftar_pengajuan.id_aktivasi', self::AKTIVASI_ID)
            ->select('barang_pengajuan.*')
            ->lockForUpdate()
            ->first();

        if (! $row) {
            $this->errors[] = "Baris {$baris}: barang_pengajuan #{$r['id']} tidak ada di aktivasi " . self::AKTIVASI_ID;

            return;
        }

        if (! $this->cekSebelum($row, $r, 'barang_pengajuan', $baris)) {
            return;
        }

        $baru = ['jumlah_disetujui' => (int) $r['jumlah_disetujui'], 'status' => (int) $r['status']];
        DB::table('barang_pengajuan')->where('id', $row->id)->update($baru + ['updated_at' => now()]);
        $this->catat('BP_UPDATE', 'barang_pengajuan', $row->id, ['jumlah_disetujui' => $row->jumlah_disetujui, 'status' => $row->status], $baru, $r['keterangan']);
    }

    private function bpInsert(array $r, int $baris): void
    {
        $daftar = $this->daftarId($r['daftar_ref'], $baris);

        if ($daftar === null) {
            return;
        }

        $sudah = DB::table('barang_pengajuan')->where('daftar_pengajuan_id', $daftar)->where('id_barang', $r['id_barang'])->exists();

        if ($sudah) {
            $this->errors[] = "Baris {$baris}: barang #{$r['id_barang']} sudah ada di daftar #{$daftar}";

            return;
        }

        $baru = [
            'daftar_pengajuan_id' => $daftar,
            'id_barang'           => (int) $r['id_barang'],
            'jumlah'              => (int) $r['jumlah'],
            'jumlah_disetujui'    => (int) $r['jumlah_disetujui'],
            'status'              => (int) $r['status'],
        ];

        $id = DB::table('barang_pengajuan')->insertGetId($baru + ['created_at' => now(), 'updated_at' => now()]);
        $this->catat('BP_INSERT', 'barang_pengajuan', $id, null, $baru, $r['keterangan']);
    }

    private function bplUpdate(array $r, int $baris): void
    {
        $row = DB::table('barang_pengajuan_lainnya')
            ->join('daftar_pengajuan', 'daftar_pengajuan.id', '=', 'barang_pengajuan_lainnya.daftar_pengajuan_id')
            ->where('barang_pengajuan_lainnya.id', $r['id'])
            ->where('daftar_pengajuan.id_aktivasi', self::AKTIVASI_ID)
            ->select('barang_pengajuan_lainnya.*')
            ->lockForUpdate()
            ->first();

        if (! $row) {
            $this->errors[] = "Baris {$baris}: barang_pengajuan_lainnya #{$r['id']} tidak ada di aktivasi " . self::AKTIVASI_ID;

            return;
        }

        if (! $this->cekSebelum($row, $r, 'barang_pengajuan_lainnya', $baris)) {
            return;
        }

        $baru = ['jumlah_disetujui' => (int) $r['jumlah_disetujui'], 'status' => (int) $r['status']];

        if ($r['vendor_nama'] !== null) {
            $baru['vendor_id'] = $this->vendor($r['vendor_nama'], $baris);
        }

        DB::table('barang_pengajuan_lainnya')->where('id', $row->id)->update($baru);
        $this->catat('BPL_UPDATE', 'barang_pengajuan_lainnya', $row->id, ['jumlah_disetujui' => $row->jumlah_disetujui, 'status' => $row->status, 'vendor_id' => $row->vendor_id], $baru, $r['keterangan']);
    }

    private function bplInsert(array $r, int $baris): void
    {
        $daftar = $this->daftarId($r['daftar_ref'], $baris);

        if ($daftar === null) {
            return;
        }

        $baru = [
            'daftar_pengajuan_id' => $daftar,
            'nama'                => $r['nama'],
            'jumlah'              => (int) $r['jumlah'],
            'jumlah_disetujui'    => (int) $r['jumlah_disetujui'],
            'kategori'            => $r['kategori'] ?? 'habis_pakai',
            'satuan'              => $r['satuan'],
            'status'              => (int) $r['status'],
            'vendor_id'           => $this->vendor($r['vendor_nama'], $baris),
            'alasan'              => 'Ditambahkan dari Excel ACC rektor 2025',
        ];

        $id = DB::table('barang_pengajuan_lainnya')->insertGetId($baru);
        $this->catat('BPL_INSERT', 'barang_pengajuan_lainnya', $id, null, $baru, $r['keterangan']);
    }

    private function verifikasiAkhir(): void
    {
        $tahunan = (int) DB::table('barang_pengajuan')
            ->join('daftar_pengajuan', 'daftar_pengajuan.id', '=', 'barang_pengajuan.daftar_pengajuan_id')
            ->where('daftar_pengajuan.id_aktivasi', self::AKTIVASI_ID)
            ->sum('barang_pengajuan.jumlah_disetujui');

        $lainnya = (int) DB::table('barang_pengajuan_lainnya')
            ->join('daftar_pengajuan', 'daftar_pengajuan.id', '=', 'barang_pengajuan_lainnya.daftar_pengajuan_id')
            ->where('daftar_pengajuan.id_aktivasi', self::AKTIVASI_ID)
            ->sum('barang_pengajuan_lainnya.jumlah_disetujui');

        $pending = DB::table('barang_pengajuan')
            ->join('daftar_pengajuan', 'daftar_pengajuan.id', '=', 'barang_pengajuan.daftar_pengajuan_id')
            ->where('daftar_pengajuan.id_aktivasi', self::AKTIVASI_ID)
            ->where('barang_pengajuan.status', self::STATUS_PENDING)
            ->count()
            + DB::table('barang_pengajuan_lainnya')
            ->join('daftar_pengajuan', 'daftar_pengajuan.id', '=', 'barang_pengajuan_lainnya.daftar_pengajuan_id')
            ->where('daftar_pengajuan.id_aktivasi', self::AKTIVASI_ID)
            ->where('barang_pengajuan_lainnya.status', self::STATUS_PENDING)
            ->count();

        $this->newLine();
        $this->table(['Pengecekan', 'Hasil di DB', 'Seharusnya (Excel)'], [
            ['Total ACC ATK Tahunan', number_format($tahunan), number_format(self::TOTAL_ACC_TAHUNAN)],
            ['Total ACC Pengajuan Lainnya', number_format($lainnya), number_format(self::TOTAL_ACC_LAINNYA)],
            ['Barang masih pending di aktivasi ' . self::AKTIVASI_ID, number_format($pending), '0'],
        ]);

        if ($tahunan !== self::TOTAL_ACC_TAHUNAN) {
            $this->errors[] = "Total ACC tahunan {$tahunan}, seharusnya " . self::TOTAL_ACC_TAHUNAN;
        }

        if ($lainnya !== self::TOTAL_ACC_LAINNYA) {
            $this->errors[] = "Total ACC lainnya {$lainnya}, seharusnya " . self::TOTAL_ACC_LAINNYA;
        }

        if ($pending !== 0) {
            $this->errors[] = "Masih ada {$pending} barang berstatus pending";
        }
    }
}
