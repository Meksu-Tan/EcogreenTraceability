<?php

declare(strict_types=1);

namespace Modules\TsShipment\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $connection = 'eudr_ts';

    protected $table = 't_shipment_header';

    protected $primaryKey = 'id_ship_head';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'entry_date',
        'from_trace_no',
        'trace_no',
        'so_no',
        'id_material_fg',
        'qty',
        'id_plant',
        'doc_url',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'qty' => 'decimal:4',
        'status' => 'integer',
    ];

    public function details()
    {
        return $this->hasMany(ShipmentDetail::class, 'id_ship_head', 'id_ship_head')
            ->where('status', 1);
    }
}
