<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('app:db-clean {--keep-master : Sisakan jabatans, unit_types, vendors, dan barang} {--force : Lewati konfirmasi}')]
#[Description('Kosongkan seluruh tabel data tanpa mengubah struktur tabel')]
class DbClean extends Command
{
    /**
     * Dikosongkan dari anak ke induk supaya foreign key tidak menggantung.
     */
    private const TRANSACTIONAL = [
        'barang_pengajuan_lainnya',
        'barang_pengajuan',
        'daftar_pengajuan',
        'aktivasi_pengajuan',
        'pengajuan',
        'pengumuman',
        'personal_access_tokens',
        'sessions',
        'password_reset_tokens',
        'users',
    ];

    private const MASTER = [
        'barang',
        'vendors',
        'unit_types',
        'jabatans',
    ];

    public function handle(): int
    {
        $tables = self::TRANSACTIONAL;

        if (! $this->option('keep-master')) {
            $tables = array_merge($tables, self::MASTER);
        }

        $tables = array_values(array_filter($tables, fn ($t) => Schema::hasTable($t)));

        $connection = DB::connection()->getName();
        $database = DB::connection()->getDatabaseName();

        $this->newLine();
        $this->line("Koneksi  : {$connection}");
        $this->line("Database : {$database}");
        $this->newLine();

        $rows = [];
        $total = 0;

        foreach ($tables as $table) {
            $count = DB::table($table)->count();
            $total += $count;
            $rows[] = [$table, number_format($count)];
        }

        $this->table(['Tabel', 'Baris yang akan dihapus'], $rows);

        if ($this->option('keep-master')) {
            $this->comment('Master data (' . implode(', ', self::MASTER) . ') dipertahankan.');
        }

        if ($total === 0) {
            $this->info('Tidak ada data untuk dihapus.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Hapus {$total} baris dari database \"{$database}\"? Tindakan ini tidak bisa dibatalkan.", false)) {
            $this->warn('Dibatalkan. Tidak ada perubahan.');

            return self::FAILURE;
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                DB::table($table)->truncate();
                $this->line("  dikosongkan: {$table}");
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->newLine();
        $this->info('Selesai. Struktur tabel tidak diubah.');

        return self::SUCCESS;
    }
}
