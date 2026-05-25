<?php

namespace Modules\Manufacturer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreManufacturerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'        => 'required|string|max:20',
            'description' => 'required|string|max:255',
            'type'        => 'nullable',
            'batch_code'  => 'nullable|string|max:50',
        ];
    }
}
