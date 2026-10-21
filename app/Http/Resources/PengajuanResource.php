<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        
        return [

            'id' => $this->id,

            'tanggal' => $this->date,

            'surat_pengajuan' => $this->surat_pengajuan,

            'user' => [
                'id' => $this->user?->id,
                'nama' => $this->user?->nama ?? $this->user?->username,
                'nip' => $this->user?->nip,

                'jabatan' => $this->user?->jabatan?->nama,

                'unit' => $this->user?->unit?->nama,
            ],

            'aktivasi' => [
                'id' => $this->aktivasi?->id,

                'mulai' => $this->aktivasi?->aktif_mulai,

                'selesai' => $this->aktivasi?->aktif_selesai,

                'tahun_akademik' => $this->aktivasi?->tahun_akademik,

                'tipe' => $this->aktivasi?->tipe,

                'jenis_pengajuan' => $this->aktivasi?->pengajuan?->tipe,

                'semester' => $this->aktivasi?->pengajuan?->semester,
            ],

            'barang' => $this->barang->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_barang' => $item->barang?->nama,
                    'kategori' => $item->barang?->kategori,
                    'unit' => $item->barang?->unit,
                    'vendor' => $item->barang?->vendor?->nama,
                    'jumlah_diajukan' => $item->jumlah,
                    'jumlah_disetujui' => $item->jumlah_disetujui,
                    'status' => $item->status,
                ];
            }),

            'barang_lainnya' => $this->barangLainnya->map(function ($item) {

                return [

                    'id' => $item->id,

                    'nama' => $item->nama,

                    'kategori' => $item->kategori,

                    'satuan' => $item->satuan,

                    'jumlah_diajukan' => $item->jumlah,

                    'jumlah_disetujui' => $item->jumlah_disetujui,

                    'status' => $item->status,

                    'bukti_foto' => $item->bukti_foto,

                    'alasan' => $item->alasan,
                ];
            }),

        ];
    }
}
