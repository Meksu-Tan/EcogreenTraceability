<?php

declare(strict_types=1);

namespace Modules\Inquiry\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plant_id' => ['nullable', 'integer'],
            'tank_id' => ['nullable', 'integer'],
            'storage_id' => ['nullable', 'integer'],
        ];
    }
}
