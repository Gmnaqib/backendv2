<?php

namespace App\Http\Requests\Jabatan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreJabatanRequest extends FormRequest
{
    public function rules()
    {
        return [
            'nama' => 'required|string|max:100|unique:jabatans,nama'
        ];
    }
}
