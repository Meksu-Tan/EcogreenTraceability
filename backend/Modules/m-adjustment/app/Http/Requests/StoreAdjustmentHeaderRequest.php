<?php
declare(strict_types=1);
namespace Modules\Adjustment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdjustmentHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_date' => ['required', 'date'],
            'id_plant' => ['required','integer'],
            'adjustment_type' => ['required', 'string'],
            'id_sloc' => ['required', 'array'],
            'id_sloc.*' => ['integer'],
            'reason' => ['required', 'string'],
        ];
    }
}
