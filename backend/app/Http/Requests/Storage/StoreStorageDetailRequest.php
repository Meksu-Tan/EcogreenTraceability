<?php

namespace App\Http\Requests\Storage;

use Illuminate\Foundation\Http\FormRequest;

class StoreStorageDetailRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'id_tank'   => 'required|integer',
            'tf_number' => 'required|string|max:50',
        ];
    }
}
