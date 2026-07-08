<?php

declare(strict_types=1);

namespace Modules\TsAcknowledge\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcknowledgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plant_code' => 'required|string',
            'entry_date' => 'required|date',
            'type' => 'nullable|string|in:WIP,TRANSFER,BLENDING',
            'section_id' => 'nullable|integer',
            'step_type' => 'nullable|string',
            'step_id' => 'nullable|integer',
            'transaction_id' => 'nullable|string',
            'eo_dls_qty' => 'nullable|numeric',
            'dcs_qty' => 'nullable|numeric',
            'keterangan' => 'nullable|string|max:500',
            'qty_source' => 'nullable|string|in:dcs,eo_dls,manual',
        ];
    }
}
