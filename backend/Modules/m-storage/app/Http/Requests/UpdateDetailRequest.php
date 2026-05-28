<?php declare(strict_types=1);

namespace Modules\Storage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tf_number' => ['required', 'string', 'max:50'],
        ];
    }
}