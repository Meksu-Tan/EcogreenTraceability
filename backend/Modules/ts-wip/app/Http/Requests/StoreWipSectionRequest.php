<?php

declare(strict_types=1);

namespace Modules\TsWip\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWipSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:eudr_ts.m_wip_section,code'],
            'name' => ['required', 'string', 'max:100'],
            'plant_id' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'integer', 'in:0,1'],
        ];
    }
}
