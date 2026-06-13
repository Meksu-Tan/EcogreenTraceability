<?php declare(strict_types=1);

namespace Modules\TsPackage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTraceNoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse' => ['required', 'integer'],
            'id_plant' => ['required', 'integer'],
        ];
    }
}