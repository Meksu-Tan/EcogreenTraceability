<?php

declare(strict_types=1);

namespace Modules\TsPackage\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $connection = 'eudr_ts';

    protected $table = 't_warehouse_header';

    protected $primaryKey = 'id_whx_head';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'entry_date',
        'from_trace_no',
        'trace_no',
        'id_material_feed',
        'id_material_fg',
        'id_section',
        'id_sloc',
        'id_plant',
        'batch_no',
        'po_no',
        'qty',
        'in_qty',
        'out_qty',
        'init_qty',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'qty' => 'decimal:4',
        'in_qty' => 'decimal:4',
        'out_qty' => 'decimal:4',
        'init_qty' => 'decimal:4',
        'status' => 'integer',
        'id_sloc' => 'array',
    ];

    public function details()
    {
        return $this->hasMany(WarehouseDetail::class, 'id_whx_head', 'id_whx_head')
            ->where('status', 1);
    }
}
