<?php declare(strict_types=1);

namespace Modules\Storage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['sometimes', 'string', 'in:activate,deactivate'],
        ];
    }
}