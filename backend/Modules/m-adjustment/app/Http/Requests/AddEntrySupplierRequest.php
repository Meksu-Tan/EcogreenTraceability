<?php
declare(strict_types=1);
namespace Modules\Adjustment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddEntrySupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_no' => ['required', 'string'],
            'id_supplier' => ['required', 'integer'],
            'batch_sap' => ['nullable', 'string'],
            'qty' => ['required', 'numeric', 'min:0'],
            'entry_date' => ['required', 'date'],
        ];
    }
}
