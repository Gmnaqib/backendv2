<?php

namespace App\Http\Requests\Pengumuman;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePengumumanRequest extends FormRequest
{
    public function rules()
    {
        return [
            'judul' => 'sometimes|string|max:255',
            'teks' => 'sometimes|string',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ];
    }

    public function messages()
    {
        return [
            'judul.max' => 'Judul pengumuman maksimal 255 karakter.',
            'gambar.image' => 'File harus berupa gambar.',
            'gambar.mimes' => 'Gambar harus berformat JPG, JPEG, PNG, atau WEBP.',
            'gambar.max' => 'Ukuran gambar maksimal 2MB.',
        ];
    }
}