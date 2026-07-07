<?php

declare(strict_types=1);

namespace Modules\TraceBackward\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShipmentTraceVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'so_no' => ['required_without:trace_no', 'nullable', 'string', 'max:20'],
            'trace_no' => ['required_without:so_no', 'nullable', 'string', 'max:100'],
        ];
    }
}
