<?php

namespace App\Http\Requests\Material;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialPackagingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'         => 'required|string|max:20',
            'code_noneudr' => 'nullable|string|max:20',
            'description'  => 'required|string|max:500',
            'id_material'  => 'required|integer',
        ];
    }
}
