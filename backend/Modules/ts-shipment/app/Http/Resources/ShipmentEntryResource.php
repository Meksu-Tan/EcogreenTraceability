<?php

declare(strict_types=1);

namespace Modules\TsShipment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_ship_head' => $this->id_ship_head,
            'entry_date' => $this->entry_date,
            'fromto_trace_no' => $this->fromto_trace_no,
            'so_no' => $this->so_no,
            'id_material_fg' => $this->id_material_fg,
            'qty' => $this->qty,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_by' => $this->updated_by,
            'updated_at' => $this->updated_at,
            'material' => $this->material,
            'id_trace_head' => $this->id_trace_head,
            'id_balance_head' => $this->id_balance_head,
            'trace_no' => $this->trace_no,
            'from_trace_no' => $this->from_trace_no,
            'batch_no' => $this->batch_no,
            'supplier' => $this->supplier,
            'balance_supplier' => $this->balance_supplier,
            'doc_url' => $this->doc_url,
            'is_last_row' => $this->is_last_row,
            'next_process' => $this->next_process,
            'plant_name' => $this->plant_name,
        ];
    }
}
