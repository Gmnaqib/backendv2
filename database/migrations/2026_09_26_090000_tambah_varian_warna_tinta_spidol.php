<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const WARNA = ['merah', 'hitam', 'biru', 'hijau'];

    private const NAMA_LAMA = 'Tinta Spidol';

    private const NAMA_ARSIP = 'Tinta Spidol (tanpa keterangan warna)';

    public function up(): void
    {
        $asal = DB::table('barang')->where('nama', self::NAMA_LAMA)->first();

        $kategori = $asal->kategori ?? 'atk_kelas';
        $tipe = $asal->tipe ?? 'habis_pakai';
        $unit = $asal->unit ?? 'Buah';
        $vendorId = $asal->vendor_id ?? null;
        $harga = $asal->harga ?? null;

        $now = now();

        foreach (self::WARNA as $warna) {
            $nama = 'Tinta Spidol warna ' . $warna;

            if (DB::table('barang')->where('nama', $nama)->exists()) {
                continue;
            }

            DB::table('barang')->insert([
                'nama' => $nama,
                'kategori' => $kategori,
                'tipe' => $tipe,
                'unit' => $unit,
                'vendor_id' => $vendorId,
                'harga' => $harga,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($asal) {
            DB::table('barang')
                ->where('id', $asal->id)
                ->update(['nama' => self::NAMA_ARSIP, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        DB::table('barang')
            ->where('nama', self::NAMA_ARSIP)
            ->update(['nama' => self::NAMA_LAMA, 'updated_at' => now()]);

        foreach (self::WARNA as $warna) {
            $barang = DB::table('barang')
                ->where('nama', 'Tinta Spidol warna ' . $warna)
                ->first();

            if (! $barang) {
                continue;
            }

            $dipakai = DB::table('barang_pengajuan')
                ->where('id_barang', $barang->id)
                ->exists();

            if (! $dipakai) {
                DB::table('barang')->where('id', $barang->id)->delete();
            }
        }
    }
};
