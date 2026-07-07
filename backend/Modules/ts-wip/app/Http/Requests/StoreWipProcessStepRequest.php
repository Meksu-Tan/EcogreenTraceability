<?php

declare(strict_types=1);

namespace Modules\TsWip\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWipProcessStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', 'exists:eudr_ts.m_wip_section,id'],
            'parent_step_id' => ['nullable', 'integer', 'exists:eudr_ts.m_wip_process_step,id'],
            'step_type' => ['required', 'string', 'in:label,feed,rundown,mode_switch'],
            'label' => ['required', 'string', 'max:200'],
            'feed_id' => ['nullable', 'required_if:step_type,feed', 'string', 'max:20'],
            'rundown_id' => ['nullable', 'required_if:step_type,rundown', 'string', 'max:20'],
            'pipe_number' => ['nullable', 'string', 'max:50'],
            'dcs_tag' => ['nullable', 'string', 'max:50'],
            'mode_group' => ['nullable', 'required_if:step_type,mode_switch', 'string', 'max:50'],
            'mode_value' => ['nullable', 'string', 'max:255'],
            'conditions' => ['nullable', 'array'],
            'mode_options' => ['nullable', 'array'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'integer', 'in:0,1'],
        ];
    }
}
