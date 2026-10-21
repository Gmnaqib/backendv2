<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255|unique:vendors,nama',
            'kontak' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
        ];
    }
}