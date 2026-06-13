<?php declare(strict_types=1);

namespace Modules\TsShipment\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentDetail extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 't_shipment_detail';
    protected $primaryKey = 'id_ship_tail';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id_ship_head',
        'id_material_fg',
        'id_supplier',
        'batch_sap',
        'qty',
        'id_plant',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'status' => 'integer',
    ];

    public function header()
    {
        return $this->belongsTo(Shipment::class, 'id_ship_head', 'id_ship_head');
    }
}
