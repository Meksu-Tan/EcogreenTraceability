<?php

declare(strict_types=1);

namespace Modules\TsShipment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SapShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'soNo' => ['required', 'string', 'max:20'],
            'soItem' => ['nullable', 'string', 'max:10'],
            'batchNo' => ['nullable', 'string', 'max:20'],
        ];
    }
}
