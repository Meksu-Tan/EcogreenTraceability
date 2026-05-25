<?php

namespace Modules\Storage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStorageTankRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'code_2'      => 'required|string|max:20',
            'code_3'      => 'required|string|max:20',
            'code_4'      => 'nullable|string|max:20',
            'id_plant'    => 'required',
            'description' => 'required|string|max:255',
        ];
    }
}
