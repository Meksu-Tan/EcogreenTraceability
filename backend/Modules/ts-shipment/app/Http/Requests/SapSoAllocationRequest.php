<?php
declare(strict_types=1);
namespace Modules\TsShipment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SapSoAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batchNo' => ['required', 'string', 'max:20'],
            'idShipHead' => ['nullable', 'integer'],
        ];
    }
}
