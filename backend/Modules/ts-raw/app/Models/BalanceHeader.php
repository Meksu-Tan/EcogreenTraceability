<?php

namespace Modules\TsRaw\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceHeader extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 't_balance_header';
    protected $primaryKey = 'id_balance_head';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'entry_date',
        'trace_no',
        'id_material',
        'id_sloc',
        'id_sloc_tail',
        'id_plant',
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
        'trace_no' => 'string',
        'qty' => 'decimal:4',
        'in_qty' => 'decimal:4',
        'out_qty' => 'decimal:4',
        'init_qty' => 'decimal:4',
        'status' => 'integer',
        'id_sloc_tail' => 'array',
    ];

    public function material()
    {
        return $this->belongsTo(\Modules\Material\Models\Material::class, 'id_material', 'id_material');
    }

    public function tank()
    {
        return $this->belongsTo(\Modules\Tank\Models\Tank::class, 'id_sloc', 'id_sloc');
    }

    public function details()
    {
        return $this->hasMany(\Modules\TsRaw\Models\BalanceDetail::class, 'id_balance_head', 'id_balance_head')
                    ->where('status', 1);
    }

    public function traceHeaders()
    {
        return $this->hasMany(\Modules\TsRaw\Models\TraceHeader::class, 'id_balance_head', 'id_balance_head');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeRmEntry($query)
    {
        return $query->whereRaw('SUBSTRING(trace_no, 1, 1) IN (1, 9)');
    }

    public function scopeTransfer($query)
    {
        return $query->whereRaw('SUBSTRING(trace_no, 1, 1) = 7');
    }
}
