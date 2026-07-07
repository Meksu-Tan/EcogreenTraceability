<?php

declare(strict_types=1);

namespace Modules\TsStock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActiveMaterialStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:WIP,WH'],
        ];
    }
}
