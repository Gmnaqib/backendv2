<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BarangPengajuanLainnyaResource extends JsonResource
{
    public function toArray($request)
    {
        return [

            'id' => $this->id,

            'nama_barang' => $this->nama,

            'jumlah' => $this->jumlah,

            'jumlah_disetujui' => $this->jumlah_disetujui,

            'kategori' => $this->kategori,

            'satuan' => $this->satuan,

            'bukti_foto' => $this->bukti_foto,

            'alasan' => $this->alasan,

            'vendor' => [
                'id' => 'default',
                'nama' => 'Perlengkapan',
            ],

            'status' => $this->status,

        ];
    }
}
