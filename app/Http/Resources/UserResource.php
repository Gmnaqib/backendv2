<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [

            'id' => $this->id,

            'nip' => $this->nip,

            'username' => $this->username,

            'email' => $this->email,

            'jenis_kelamin' => $this->jenis_kelamin,

            'foto' => $this->foto,

            'jabatan' => [
                'id' => $this->jabatan?->id,
                'nama' => $this->jabatan?->nama,
            ],

            'unit' => [
                'id' => $this->unit?->id,
                'nama' => $this->unit?->nama,
            ],

            'created_at' => $this->created_at,
        ];
    }
}
