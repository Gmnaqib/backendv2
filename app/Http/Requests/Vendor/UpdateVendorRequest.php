<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('vendor')->id ?? $this->route('vendor');

        return [
            'nama' => "required|string|max:255|unique:vendors,nama,$id",
            'kontak' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
        ];
    }
}
