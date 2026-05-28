<?php declare(strict_types=1);
namespace Modules\Tank\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTankRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id'          => 'nullable|integer',
            'plant_code'  => 'required|string|max:10',
            'plant_name'  => 'required|string|max:100',
            'tank_number' => 'required|string|max:50',
            'tank_height' => 'required|numeric',
        ];
    }
}
