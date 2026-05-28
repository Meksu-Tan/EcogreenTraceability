<?php declare(strict_types=1);
namespace Modules\Storage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'id_batch'    => 'required',
            'code'        => 'required|string|max:20',
            'description' => 'required|string|max:255',
        ];
    }
}
