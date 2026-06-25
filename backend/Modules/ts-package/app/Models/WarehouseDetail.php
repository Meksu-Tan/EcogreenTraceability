<?php
declare(strict_types=1);
namespace Modules\TsPackage\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseDetail extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 't_warehouse_detail';
    protected $primaryKey = 'id_whx_tail';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id_whx_head',
        'id_material_feed',
        'id_material_fg',
        'id_supplier',
        'id_sloc',
        'id_plant',
        'batch_sap',
        'qty',
        'in_qty',
        'out_qty',
        'init_qty',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'in_qty' => 'decimal:4',
        'out_qty' => 'decimal:4',
        'init_qty' => 'decimal:4',
        'status' => 'integer',
        'id_sloc' => 'array',
    ];

    public function header()
    {
        return $this->belongsTo(Package::class, 'id_whx_head', 'id_whx_head');
    }
}
