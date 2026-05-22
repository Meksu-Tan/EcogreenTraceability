<?php

namespace Modules\TsRaw\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'entry_date'       => 'required|date',
            'id_balance_head'  => 'required|integer',
            'id_dest_tank'     => 'required|integer',
            'id_dest_tank_tail' => 'required|array',
            'qty'             => 'required|numeric|min:0.001',
            'id_plant'        => 'nullable|integer',
        ];
    }
}
