<?php
declare(strict_types=1);
namespace Modules\Quantifier\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuantifierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'mode' => ['nullable', 'string', 'in:ADD,UPDATE'],
            'flowmeter' => ['nullable', 'string'],
            'reset_date' => ['required', 'date'],
            'value' => ['required', 'numeric'],
            'remark' => ['nullable', 'string'],
        ];
    }
}
