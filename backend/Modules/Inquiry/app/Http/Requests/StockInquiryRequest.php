<?php declare(strict_types=1);
namespace Modules\Inquiry\Http\Requests;

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
            'material_id' => ['nullable', 'integer'],
            'storage_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}