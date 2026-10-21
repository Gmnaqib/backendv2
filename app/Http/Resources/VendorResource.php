<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'kontak' => $this->kontak,
            'alamat' => $this->alamat,
            'barang' => BarangResource::collection($this->whenLoaded('barang')),
            'created_at' => $this->created_at,
        ];
    }
}