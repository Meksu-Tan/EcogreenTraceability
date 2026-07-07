<?php

declare(strict_types=1);

namespace Modules\TsWip\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderWipProcessStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:m_wip_process_step,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:1'],
        ];
    }
}
