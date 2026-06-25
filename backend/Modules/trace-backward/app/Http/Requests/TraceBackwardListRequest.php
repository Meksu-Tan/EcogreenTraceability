<?php
declare(strict_types=1);
namespace Modules\TraceBackward\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceBackwardListRequest extends FormRequest
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
            'sort_by'  => ['nullable', 'string', 'in:entry_date,trace_no,so_no,material,batch_no,supplier'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
