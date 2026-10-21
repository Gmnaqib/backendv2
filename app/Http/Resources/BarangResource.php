<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $jumlah = $this->barang_pengajuan_sum_jumlah_disetujui ?? $this->barang_pengajuan_sum_jumlah ?? 0;

        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'kategori' => $this->kategori,
            'tipe' => $this->tipe,
            'unit' => $this->unit,
            'harga' => $this->harga,
            'jumlah' => (int) $jumlah,
            'sub_total' => (int) $jumlah * $this->harga,
            'vendor' => [
                'id' => $this->vendor?->id,
                'nama' => $this->vendor?->nama
            ],

            'created_at' => $this->created_at
        ];
    }
}
