<?php declare(strict_types=1);

namespace Modules\TsPackage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePackageEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'poNo' => ['nullable', 'string', 'max:50'],
            'batchNo' => ['nullable', 'string', 'max:50'],
            'warehouse' => ['nullable', 'integer'],
            'idTankTail' => ['nullable', 'array'],
        ];
    }
}
