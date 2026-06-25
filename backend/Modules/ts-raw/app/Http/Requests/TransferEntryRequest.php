<?php
declare(strict_types=1);
namespace Modules\TsRaw\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_date' => 'required|date',
            'entry_no' => 'required|string',
            'source_tank' => 'required',
            'trf_tank' => 'required',
            'tank_no' => 'present|array',
            'trf_tank_no' => 'present|array',
            'material_document' => 'nullable|string',
        ];
    }
}
