<?php

declare(strict_types=1);

namespace Modules\TsStock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plant_id' => ['nullable', 'integer'],
            'id_plant' => ['nullable', 'integer'],
            'material_id' => ['nullable', 'string'],
            'storage_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'report_type' => ['nullable', 'string', 'in:detail,summary'],
            'mode' => ['nullable', 'string'],
        ];
    }
}
