<?php

namespace Modules\Storage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStorageDetailRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'id_sloc'   => 'required|integer',
            'tf_number' => 'required|string|max:50',
        ];
    }
}
