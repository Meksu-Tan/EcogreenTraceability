<?php
declare(strict_types=1);
namespace Modules\Dashboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetStatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}