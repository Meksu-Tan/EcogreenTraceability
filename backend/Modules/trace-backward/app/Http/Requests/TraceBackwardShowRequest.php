<?php declare(strict_types=1);
namespace Modules\TraceBackward\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceBackwardShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_material' => ['nullable', 'integer'],
            'id_plant' => ['nullable'],
        ];
    }
}
