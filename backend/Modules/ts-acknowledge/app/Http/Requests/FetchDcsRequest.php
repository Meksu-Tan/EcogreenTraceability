<?php

declare(strict_types=1);

namespace Modules\TsAcknowledge\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FetchDcsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plant_code' => 'required|string',
            'date' => 'required|date',
            'type' => 'nullable|string|in:WIP,TRANSFER,BLENDING',
            'scope' => 'nullable|string|in:row,all',
            'section_id' => 'nullable|integer',
            'step_type' => 'nullable|string|in:feed,rundown',
            'step_id' => 'nullable|integer',
            'transaction_id' => 'nullable|string',
        ];
    }
}
