<?php

namespace App\Http\Requests\Barang;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',

            'kategori' => [
                'required',
                'in:atk_tahunan,atk_ujian,atk_kelas'
            ],

            'harga' => 'nullable|numeric|min:0',

            'tipe' => [
                'required',
                'in:habis_pakai,tidak_habis_pakai'
            ],

            'unit' => 'required|string|max:100',

            'vendor_id' => 'required|exists:vendors,id'
        ];
    }
}
