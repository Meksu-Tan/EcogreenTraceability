<?php
declare(strict_types=1);
namespace Modules\TsPackage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePackageEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entryDate' => ['required', 'date'],
            'fgProduct' => ['required', 'integer'],
            'batchNo' => ['required', 'string', 'max:50'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'poNo' => ['nullable', 'string', 'max:50'],
            'tank' => ['required', 'integer'],
            'tankNo' => ['required', 'array'],
            'warehouse' => ['required', 'integer'],
        ];
    }
}
