<?php

namespace App\Http\Requests\AktivasiPengajuan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAktivasiPengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_pengajuan' => 'sometimes|exists:pengajuan,id',
            'aktif_mulai' => 'sometimes|date',
            'aktif_selesai' => 'sometimes|date|after:aktif_mulai',
            'tipe' => 'sometimes|in:rutin,nonrutin',
            'tahun_akademik' => 'sometimes|string|max:20',
            'semester' => 'nullable|in:ganjil,genap,tahunan',
            'ujian' => 'nullable|in:uts,uas,Default,default'
        ];
    }
}