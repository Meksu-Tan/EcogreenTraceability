<?php

namespace App\Http\Requests\Storage;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'id_batch'    => 'required',
            'code'        => 'required|string|max:20',
            'description' => 'required|string|max:255',
        ];
    }
}
