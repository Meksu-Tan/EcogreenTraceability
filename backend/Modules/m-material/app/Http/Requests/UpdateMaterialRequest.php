<?php

namespace Modules\Material\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'               => 'required|string|max:20',
            'code_noneudr'       => 'nullable|string|max:20',
            'description'        => 'required|string|max:500',
            'type'               => 'required|string|max:20',
            'yield'              => 'nullable|numeric|min:0|max:100',
            'qtf_feed'           => 'nullable|string|max:20',
            'qtf_rundown'        => 'nullable|string|max:20',
            'status_packaging'   => 'nullable|in:0,1',
            'code_matl_supplier' => 'nullable|string|max:20',
        ];
    }
}
