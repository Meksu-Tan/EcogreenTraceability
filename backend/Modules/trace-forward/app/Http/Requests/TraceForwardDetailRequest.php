<?php declare(strict_types=1);
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
            'id_header' => ['nullable', 'integer'],
            'trace_no' => ['nullable', 'string', 'max:100'],
            'id_material' => ['nullable', 'integer'],
            'id_plant' => ['nullable'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (!$this->input('id_header') && !$this->input('trace_no')) {
                $v->errors()->add('lookup', 'Either id_header or trace_no is required');
            }
        });
    }
}
