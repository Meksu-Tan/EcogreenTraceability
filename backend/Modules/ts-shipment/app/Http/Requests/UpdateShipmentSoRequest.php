<?php declare(strict_types=1);

namespace Modules\TsShipment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentSoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'soNo' => ['required', 'string'],
        ];
    }
}
