<?php

declare(strict_types=1);

namespace Modules\Adjustment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdjustmentWhxRequest extends FormRequest
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
            'qty' => ['required', 'numeric'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
