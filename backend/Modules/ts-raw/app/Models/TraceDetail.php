<?php declare(strict_types=1);
namespace Modules\TsRaw\Models;

use Illuminate\Database\Eloquent\Model;

class TraceDetail extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 't_trace_detail';
    protected $primaryKey = 'id_trace_tail';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id_trace_head',
        'id_supplier',
        'id_material',
        'batch_sap',
        'in_qty',
        'out_qty',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'in_qty' => 'decimal:4',
        'out_qty' => 'decimal:4',
        'status' => 'integer',
    ];

    public function traceHeader()
    {
        return $this->belongsTo(\Modules\TsRaw\Models\TraceHeader::class, 'id_trace_head', 'id_trace_head');
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
}
