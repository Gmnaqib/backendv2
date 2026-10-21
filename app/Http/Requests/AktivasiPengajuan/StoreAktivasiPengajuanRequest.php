<?php

namespace App\Http\Requests\AktivasiPengajuan;

use Illuminate\Foundation\Http\FormRequest;

class StoreAktivasiPengajuanRequest extends FormRequest
{

    public function rules(): array
    {
        return [

            'id_pengajuan' => 'required|exists:pengajuan,id',
            'aktif_mulai' => 'required|date',
            'aktif_selesai' => 'required|date|after:aktif_mulai',
            'tipe' => 'required|in:rutin,nonrutin',
            'tahun_akademik' => 'required|string|max:20',
            'semester' => 'nullable|in:ganjil,genap,tahunan',
            'ujian' => 'nullable|in:uts,uas,Default,default'
        ];
    }
}