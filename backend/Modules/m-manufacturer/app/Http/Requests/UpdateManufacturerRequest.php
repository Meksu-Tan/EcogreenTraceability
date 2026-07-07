<?php

declare(strict_types=1);

namespace Modules\Manufacturer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManufacturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'material_type' => 'nullable|string|max:100',
        ];
    }
}
