<?php
declare(strict_types=1);
namespace Modules\TsTransfer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $flag = $this->input('flag');

        if (!$flag) {
            $path = $this->path();
            if ($this->isMethod('POST')) {
                if (str_ends_with($path, '/transfers')) {
                    $flag = 'post_transferEntry';
                } elseif (str_ends_with($path, '/matl-doc')) {
                    $flag = 'post_matlDocNumber';
                } elseif (str_ends_with($path, '/update-sub-tank')) {
                    $flag = 'post_updateEntrySubTank';
                }
            }
        }

        return match ($flag) {
            'post_transferEntry' => [
                'entry_no' => 'required|string',
                'entry_date' => 'required|date',
                'id_material' => 'required|integer',
                'material_doc' => 'nullable|string',
                'trf_qty' => 'required|numeric|min:0.001',
                'source_sloc' => 'required|integer|different:trf_sloc',
                'trf_sloc' => 'required|integer',
                'source_sloc_no' => 'nullable|array',
                'trf_sloc_no' => 'nullable|array',
                'trf_type' => 'nullable|string|in:in,out,all',
                'supplierCode' => 'nullable|string',
                'idSupplier' => 'nullable|integer',
            ],
            'post_matlDocNumber' => [
                'id' => 'required|integer',
                'number' => 'required|string',
                'mode' => 'required|string|in:ADD,UPDATE',
            ],
            'post_updateEntrySubTank' => [
                'idHead' => 'required|integer',
                'idSlocTail' => 'required|array',
            ],
            default => [
                'flag' => 'required|string|in:post_transferEntry,post_matlDocNumber,post_updateEntrySubTank',
            ],
        };
    }
}
