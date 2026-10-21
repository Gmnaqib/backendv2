<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BarangPengajuanResource extends JsonResource
{
    public function toArray($request)
    {
        return [

            'id' => $this->id,

            'id_barang' => $this->id_barang,

            'nama_barang' => $this->whenLoaded('barang', function () {
                return $this->barang->nama;
            }),

            'jumlah_diajukan' => $this->jumlah,

            'jumlah_disetujui' => $this->jumlah_disetujui,

            'vendor' => $this->whenLoaded('barang', function () {
                return [
                    'id' => $this->barang->vendor->id ?? null,
                    'nama' => $this->barang->vendor->nama ?? null,
                ];
            }),

            'status' => $this->status,

        ];
    }
}