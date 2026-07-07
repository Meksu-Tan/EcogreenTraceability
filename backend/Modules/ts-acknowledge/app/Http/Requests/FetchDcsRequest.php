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
        ];
    }
}
