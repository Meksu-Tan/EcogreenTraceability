<?php

declare(strict_types=1);

namespace Modules\TraceBackward\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentHeader extends Model
{
    protected $connection = 'eudr_ts';

    protected $table = 't_shipment_header';

    protected $primaryKey = 'id_ship_head';

    public $timestamps = true;

    protected $fillable = [
        'entry_date',
        'trace_no',
        'from_trace_no',
        'id_material_fg',
        'id_sloc',
        'id_plant',
        'so_no',
        'batch_no',
        'qty',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'trace_no' => 'string',
        'from_trace_no' => 'string',
        'qty' => 'decimal:4',
        'status' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
