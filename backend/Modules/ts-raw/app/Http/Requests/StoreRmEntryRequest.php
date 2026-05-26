<?php

namespace Modules\TsRaw\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRmEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'entry_date'        => 'required|date',
            'rm_number'         => 'required|string',
            'id_material'       => 'required|integer',
            'id_sloc'           => 'required|integer',
            'id_sloc_tail'      => 'present|array',
            'id_tank'           => 'nullable|integer',
            'id_tank_tail'      => 'nullable|array',
            'total_qty'         => 'required|numeric|min:0.001',
            'material_document' => 'nullable|string',
            'po_so'             => 'nullable|string',
        ];
    }
}
