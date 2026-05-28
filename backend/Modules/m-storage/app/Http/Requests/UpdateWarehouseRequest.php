<?php declare(strict_types=1);

namespace Modules\Storage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_batch'    => ['required', 'string', 'max:50'],
            'code'        => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
        ];
    }
}