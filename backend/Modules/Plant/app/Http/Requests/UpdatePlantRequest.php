<?php

namespace Modules\Plant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'        => 'nullable|string',
            'code_2'      => 'required|string',
            'code_3'      => 'required|string',
            'description' => 'required|string',
        ];
    }
}
