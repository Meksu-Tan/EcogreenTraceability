<?php declare(strict_types=1);
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
            'id_plant' => ['required'],
            'adjustment_type' => ['required', 'string'],
            'reason' => ['required', 'string'],
        ];
    }
}
