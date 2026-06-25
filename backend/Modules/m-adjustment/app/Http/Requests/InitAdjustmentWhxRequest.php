<?php
declare(strict_types=1);
namespace Modules\Adjustment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitAdjustmentWhxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_plant' => ['required'],
            'entry_date' => ['required', 'date'],
            'id_material' => ['required', 'integer'],
            'tf_number' => ['required', 'integer'],
            'value' => ['required', 'numeric'],
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }
}
