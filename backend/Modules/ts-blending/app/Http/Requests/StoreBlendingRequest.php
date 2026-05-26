<?php

namespace Modules\TsBlending\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlendingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $flag = $this->input('flag');

        return match ($flag) {
            'post_blendingEntryMaterial' => [
                'entryNo' => 'required|string',
                'idMaterialSource' => 'required|integer',
                'qty' => 'required|numeric|min:0.001',
                'idTank' => 'nullable|integer',
                'mode' => 'required|string|in:ADD,EDIT',
            ],
            'post_blendingEntry' => [
                'entry_no' => 'required|string',
                'entry_date' => 'required|date',
                'id_material' => 'required|integer',
                'material_doc' => 'nullable|string',
                'qty' => 'required|numeric|min:0.001',
                'tankNo' => 'nullable|array',
            ],
            'post_matlDocNumber' => [
                'id' => 'required|integer',
                'number' => 'required|string',
                'mode' => 'required|string',
            ],
            'post_updateEntrySubTank' => [
                'idHead' => 'required|integer',
                'idTankTail' => 'required|array',
            ],
            'delete_blendingMaterial' => [
                'id' => 'required|integer',
            ],
            default => [
                'flag' => 'required|string|in:post_blendingEntryMaterial,post_blendingEntry,post_matlDocNumber,post_updateEntrySubTank,delete_blendingMaterial',
            ],
        };
    }
}
