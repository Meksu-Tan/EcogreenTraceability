<?php
declare(strict_types=1);
namespace Modules\TsRaw\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierTempRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'entry_no'      => 'required|string',
            'id_supplier'   => 'nullable|integer',
            'id_material'   => 'required|integer',
            'id_manufacturer' => 'nullable',
            'qty'          => 'required|numeric|min:0.001',
            'batch_sap'    => 'nullable|string',
        ];
    }
}
