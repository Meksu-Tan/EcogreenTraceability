<?php

declare(strict_types=1);

namespace Modules\TsTransfer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_balance_head' => ['required', 'integer'],
        ];
    }
}
