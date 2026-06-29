<?php
declare(strict_types=1);

namespace Modules\TsTsreport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_date' => ['nullable', 'date'],
            'plant_id' => ['nullable', 'integer'],
            'id_plant' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'in:wip,package,shipment,transfer,blending'],
        ];
    }
}
