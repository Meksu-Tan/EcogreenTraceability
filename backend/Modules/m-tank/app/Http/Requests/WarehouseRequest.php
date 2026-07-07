<?php

declare(strict_types=1);

namespace Modules\Tank\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_batch' => 'nullable|string|max:20',
            'code' => 'required|string|max:20',
            'description' => 'required|string|max:100',
        ];
    }
}
