<?php
declare(strict_types=1);
namespace Modules\TraceBackward\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceBackwardDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trace_no' => ['required', 'string', 'regex:/^[1-9]\d{10}$|^[1-9]\d{13}$/'],
            'id_material' => ['nullable', 'integer'],
            'id_plant' => ['nullable'],
        ];
    }
}
