<?php declare(strict_types=1);

namespace Modules\TsRaw\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRmEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Using flexible validation since the original used $request->all() directly
            'entry_date' => 'sometimes|required|date',
            'material_document' => 'sometimes|nullable|string',
            'po_so' => 'sometimes|nullable|string',
            'total_qty' => 'sometimes|required|numeric|min:0.001',
            'id_sloc' => 'sometimes|required',
            'id_tank' => 'sometimes|nullable',
        ];
    }
}
