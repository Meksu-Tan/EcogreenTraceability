<?php
declare(strict_types=1);
namespace Modules\TsShipment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTraceNoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_plant' => ['required', 'integer', 'min:1'],
            'id_material' => ['required', 'string'],
        ];
    }
}