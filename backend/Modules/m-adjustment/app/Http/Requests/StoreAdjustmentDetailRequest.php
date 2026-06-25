<?php
declare(strict_types=1);
namespace Modules\Adjustment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdjustmentDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_adjust_head' => ['required', 'integer'],
            'id_material' => ['required', 'integer'],
            'tf_number' => ['required', 'integer'],
            'qty' => ['required', 'numeric'],
            'qty_before' => ['nullable', 'numeric'],
            'qty_after' => ['nullable', 'numeric'],
            'lot_no' => ['nullable', 'string'],
            'expiry_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
