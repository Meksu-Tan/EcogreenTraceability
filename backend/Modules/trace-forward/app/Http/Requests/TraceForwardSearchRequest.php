<?php

declare(strict_types=1);

namespace Modules\TraceForward\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceForwardSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_material' => ['required', 'integer'],
            'batch_no' => ['nullable', 'string', 'max:100'],
            'id_plant' => ['nullable'],
        ];
    }
}
