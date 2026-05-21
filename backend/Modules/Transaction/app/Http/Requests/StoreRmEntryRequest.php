<?php

namespace Modules\Transaction\Http\Requests;

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
            'id_tank'           => 'required|integer',
            'id_tank_tail'      => 'present|array',
            'total_qty'         => 'required|numeric|min:0.001',
            'material_document' => 'nullable|string',
            'po_so'             => 'nullable|string',
        ];
    }
}
