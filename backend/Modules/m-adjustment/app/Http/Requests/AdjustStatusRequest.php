<?php

declare(strict_types=1);

namespace Modules\Adjustment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjust_no' => ['nullable', 'string'],
            'id_adjust_head' => ['nullable', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (! $this->input('adjust_no') && ! $this->input('id_adjust_head')) {
                $v->errors()->add('lookup', 'Either adjust_no or id_adjust_head must be provided');
            }
        });
    }
}
