<?php declare(strict_types=1);

namespace Modules\Storage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code_2'       => ['required', 'string', 'max:50'],
            'code_3'       => ['required', 'string', 'max:50'],
            'code_4'       => ['nullable', 'string', 'max:50'],
            'id_plant'     => ['required', 'string', 'max:50'],
            'description'  => ['required', 'string', 'max:255'],
        ];
    }
}