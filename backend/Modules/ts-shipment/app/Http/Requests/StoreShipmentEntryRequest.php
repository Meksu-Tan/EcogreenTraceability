<?php
declare(strict_types=1);
namespace Modules\TsShipment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entryDate' => ['required', 'date'],
            'fgProduct' => ['required', 'string'],
            'soNo' => ['required', 'string', 'max:50'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'batch_no' => ['required', 'string', 'max:50'],
            'filename' => ['nullable', 'string', 'max:255'],
        ];
    }
}
