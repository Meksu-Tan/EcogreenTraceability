<?php

declare(strict_types=1);

namespace Modules\TsAcknowledge\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plant_code' => 'required|string',
            'date' => 'required_with:type,WIP|date',
            'type' => 'nullable|string|in:WIP,TRANSFER,BLENDING',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'section_id' => 'nullable|integer|exists:eudr_ts.m_wip_section,id',
        ];
    }
}
