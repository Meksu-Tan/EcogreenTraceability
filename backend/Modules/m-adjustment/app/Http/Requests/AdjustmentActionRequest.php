<?php

declare(strict_types=1);

namespace Modules\Adjustment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustmentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable|integer',
            'id_head' => 'nullable|integer',
            'entry_date' => 'nullable|date',
            'entry_no' => 'nullable|string',
            'adjust_no' => 'nullable|string',
            'id_material' => 'nullable|integer',
            'tf_number' => 'nullable|integer',
            'id_supplier' => 'nullable|integer',
            'qty' => 'nullable|numeric',
            'batch_sap' => 'nullable|string',
            'material_doc' => 'nullable|string',
            'mode' => 'nullable|string|in:ADD,EDIT,VIEW',
            'status' => 'nullable|integer|in:1,2,3,4,5',
            'reason' => 'nullable|string|max:500',
        ];
    }
}
