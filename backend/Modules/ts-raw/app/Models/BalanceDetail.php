<?php
declare(strict_types=1);
namespace Modules\TsRaw\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Helpers\ResponseCode;

class BalanceDetail extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 't_balance_detail';
    protected $primaryKey = 'id_balance_tail';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id_balance_head',
        'id_supplier',
        'id_material',
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
    ];

    public function balanceHeader()
    {
        return $this->belongsTo(\Modules\TsRaw\Models\BalanceHeader::class, 'id_balance_head', 'id_balance_head');
    }

    public function supplier()
    {
        return $this->belongsTo(\Modules\Supplier\Models\Supplier::class, 'id_supplier', 'id_supplier');
    }

    public function material()
    {
        return $this->belongsTo(\Modules\Material\Models\Material::class, 'id_material', 'id_material');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeAvailable($query)
    {
        return $query->where('qty', '>', ResponseCode::QUANTITY_PRECISION_THRESHOLD);
    }
}
