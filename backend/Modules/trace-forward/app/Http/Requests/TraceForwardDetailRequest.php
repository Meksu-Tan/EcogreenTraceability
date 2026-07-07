<?php

declare(strict_types=1);

namespace Modules\TraceForward\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceForwardDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trace_no' => ['required', 'string', 'max:100'],
            'id_material' => ['required', 'integer', 'min:1'],
            'id_plant' => ['nullable'],
        ];
    }
}
