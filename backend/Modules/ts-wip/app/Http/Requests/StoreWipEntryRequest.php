<?php

declare(strict_types=1);

namespace Modules\TsWip\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWipEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'flag' => 'nullable|string',
            'mode' => 'nullable|string',
            'feature' => 'nullable|string',
            'feed_id' => 'nullable|string',
            'rundown_id' => 'nullable|string',
            'tank' => 'nullable|integer',
            'tankNo' => 'nullable|array',
            'batch_no' => 'nullable|string',
            'last_feed' => 'nullable|numeric',
            'curr_feed' => 'nullable|numeric',
            'last_rundown' => 'nullable|numeric',
            'curr_rundown' => 'nullable|numeric',
            'curr_entryDate' => 'nullable|date',
            'traceNo' => 'nullable|string',
            'idHead' => 'nullable|integer',
            'idSlocTail' => 'nullable|array',
            'id' => 'nullable|integer',
            'number' => 'nullable|string',
            'wip_mode' => 'nullable|integer',
        ];
    }
}
