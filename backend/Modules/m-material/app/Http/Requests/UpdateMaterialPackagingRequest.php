<?php
declare(strict_types=1);
namespace Modules\Material\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialPackagingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'         => 'required|string|max:20',
            'code_noneudr' => 'nullable|string|max:20',
            'description'  => 'required|string|max:500',
            'id_material'  => 'required|integer',
        ];
    }
}
