<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\DaftarPengajuan;
use App\Support\PengajuanCache;
use Illuminate\Http\Request;

class CetakBerkasController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'tahun'         => 'required',
            'id_aktivasi'   => 'required|integer',
            'tipe'          => 'nullable|in:rutin,nonrutin',
            'kategori_atk'  => 'nullable|in:tahunan,kelas,ujian',
        ]);

        $tahun = $request->tahun;
        $aktivasi = $request->id_aktivasi;
        $tipe = $request->tipe;
        $kategoriAtk = $request->kategori_atk;

        $hasil = PengajuanCache::remember('cetak-berkas', [
            'tahun'        => $tahun,
            'aktivasi'     => $aktivasi,
            'tipe'         => $tipe,
            'kategori_atk' => $kategoriAtk,
        ], function () use ($tahun, $aktivasi, $tipe, $kategoriAtk) {
            return $this->rakit($tahun, $aktivasi, $tipe, $kategoriAtk);
        });

        return ApiResponse::success($hasil);
    }

    private function rakit($tahun, $aktivasi, $tipe, $kategoriAtk): array
    {
        $query = DaftarPengajuan::with([
            'aktivasi',
            'aktivasi.pengajuan',
            'barang.barang.vendor',
            'barangLainnya',
        ])
        // id_aktivasi sudah mengunci data ke satu periode. Menyaring lagi
        // dengan tahun dibuatnya baris hanya bisa salah membuang: untuk
        // periode 2026/2027 frontend mengirim tahun 2027, sedangkan
        // pengajuannya dibuat pada 2026.
        ->where('id_aktivasi', $aktivasi);

        
        if ($tipe) {

            $query->whereHas('aktivasi', function ($q) use ($tipe) {

                $q->where('tipe', $tipe);

            });
        }

        if ($kategoriAtk) {

            $query->whereHas('aktivasi.pengajuan', function ($q) use ($kategoriAtk) {

                $q->where('tipe', $kategoriAtk);

            });
        }

        $pengajuan = $query
            ->latest()
            ->get();

        $barangPengajuan = collect();

        $barangPengajuanLainnya = collect();

        foreach ($pengajuan as $data) {

            foreach ($data->barang as $barang) {

                $barangPengajuan->push([
                    'nama_barang' => $barang->barang->nama ?? '-',
                    'unit' => $barang->barang->unit ?? '-',
                    'jumlah'      => $barang->jumlah ?? 0,
                    'harga'       => $barang->barang->harga ?? 0,
                    'subtotal'    => ($barang->jumlah ?? 0) * ($barang->barang->harga ?? 0),
                    'vendor'      => $barang->barang->vendor->nama ?? '-',
                ]);
            }

            foreach ($data->barangLainnya as $barangLainnya) {

                $barangPengajuanLainnya->push([
                    'nama_barang' => $barangLainnya->nama ?? '-',
                    'kategori'    => $barangLainnya->kategori ?? '-',
                    'satuan'      => $barangLainnya->satuan ?? '-',
                    'jumlah'      => $barangLainnya->jumlah ?? 0,
                    'harga'       => 0,
                    'subtotal'    => 0,
                    'vendor'      => 'Perlengkapan',
                ]);
            }
        }

        return [
            'tahun'                     => $tahun,
            'id_aktivasi'               => $aktivasi,
            'tipe'                      => $tipe,
            'kategori_atk'              => $kategoriAtk,

            'total_harga' =>

                $barangPengajuan->sum('subtotal') +

                $barangPengajuanLainnya->sum('subtotal'),

            'barang_pengajuan'          => $barangPengajuan->values()->all(),

            'barang_pengajuan_lainnya'  => $barangPengajuanLainnya->values()->all(),
        ];
    }
}
