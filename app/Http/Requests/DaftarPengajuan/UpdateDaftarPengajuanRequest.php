<?php

namespace App\Http\Requests\DaftarPengajuan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDaftarPengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'date' => [
                'sometimes',
                'date'
            ],

            'surat_pengajuan' => [
                'sometimes',
                'file',
                'mimes:pdf',
                'max:2048'
            ]

        ];
    }

    public function messages(): array
    {
        return [

            'date.date' => 'Format tanggal tidak valid.',

            'surat_pengajuan.file' => 'Surat pengajuan harus berupa file.',
            'surat_pengajuan.mimes' => 'Surat pengajuan harus berformat PDF.',
            'surat_pengajuan.max' => 'Ukuran surat pengajuan maksimal 2MB.'

        ];
    }
}