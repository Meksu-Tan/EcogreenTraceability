<?php
declare(strict_types=1);
namespace Modules\TsBlending\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlendingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation()
    {
        if ($this->has('qty')) {
            $this->merge([
                'qty' => str_replace(',', '', (string)$this->qty)
            ]);
        }

        if ($this->route('id')) {
            $this->merge([
                'id' => $this->route('id')
            ]);
        }
    }

    public function rules(): array
    {
        $flag = $this->input('flag');

        if (!$flag) {
            $path = $this->path();
            if ($this->isMethod('POST')) {
                if (str_ends_with($path, '/material')) {
                    $flag = 'post_blendingEntryMaterial';
                } elseif (str_ends_with($path, '/execute')) {
                    $flag = 'post_blendingEntry';
                } elseif (str_ends_with($path, '/matl-doc')) {
                    $flag = 'post_matlDocNumber';
                } elseif (str_ends_with($path, '/update-sub-tank')) {
                    $flag = 'post_updateEntrySubTank';
                }
            } elseif ($this->isMethod('DELETE') && str_contains($path, '/material/')) {
                $flag = 'delete_blendingMaterial';
            }
        }

        return match ($flag) {
            'post_blendingEntryMaterial' => [
                'entryNo' => 'required|string',
                'idMaterialSource' => 'required|integer',
                'qty' => 'required|numeric|min:0.001',
                'idSloc' => 'nullable|integer',
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
                'idSlocTail' => 'required|array',
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
