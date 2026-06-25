<?php
declare(strict_types=1);
namespace Modules\TraceForward\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceForwardListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'id_plant' => ['nullable'],
            'search'   => ['nullable', 'string', 'max:100'],
            'sort_by'  => ['nullable', 'string', 'in:entry_date,trace_no,material,batch_sap,supplier'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
