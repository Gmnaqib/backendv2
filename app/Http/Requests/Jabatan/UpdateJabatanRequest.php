<?php

namespace App\Http\Requests\Jabatan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJabatanRequest extends FormRequest
{
    public function rules()
    {
        
        $id = $this->route('jabatan')->id ?? $this->route('jabatan');
        return [
            'nama' => 'sometimes|string|max:100|unique:jabatans,nama,' . $id
        ];
    }
}
