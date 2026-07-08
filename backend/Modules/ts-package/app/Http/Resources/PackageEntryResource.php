<?php

declare(strict_types=1);

namespace Modules\TsPackage\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_whx_head' => $this->id_whx_head,
            'entry_date' => $this->entry_date,
            'fromto_trace_no' => $this->fromto_trace_no,
            'id_material_feed' => $this->id_material_feed,
            'id_material_fg' => $this->id_material_fg,
            'batch_no' => $this->batch_no,
            'id_trace_head' => $this->id_trace_head,
            'id_balance_head' => $this->id_balance_head,
            'sloc' => $this->sloc,
            'raw_sloc' => $this->raw_sloc,
            'init_qty' => $this->init_qty,
            'balance' => $this->balance,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_by' => $this->updated_by,
            'updated_at' => $this->updated_at,
            'feed' => $this->feed,
            'fg' => $this->fg,
            'trace_no' => $this->trace_no,
            'po_no' => $this->po_no,
            'whx' => $this->whx,
            'id_section' => $this->id_section,
            'supplier' => $this->supplier,
            'balance_supplier' => $this->balance_supplier,
            'is_last_row' => $this->is_last_row,
            'next_process' => $this->next_process,
            'plant_name' => $this->plant_name,
        ];
    }
}
