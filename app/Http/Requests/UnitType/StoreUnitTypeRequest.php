<?php

namespace App\Http\Requests\UnitType;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUnitTypeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'nama' => 'required|string|max:150',
            'parent_id' => 'nullable|exists:unit_types,id'
        ];
    }
}
