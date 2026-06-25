<?php
declare(strict_types=1);
namespace Modules\TsRaw\Models;

use Illuminate\Database\Eloquent\Model;

class TraceHeader extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 't_trace_header';
    protected $primaryKey = 'id_trace_head';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id_balance_head',
        'entry_date',
        'from_trace_no',
        'to_trace_no',
        'id_material',
        'id_sloc',
        'id_sloc_tail',
        'id_plant',
        'in_qty',
        'out_qty',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'in_qty' => 'decimal:4',
        'out_qty' => 'decimal:4',
        'status' => 'integer',
        'id_sloc_tail' => 'array',
    ];

    public function balanceHeader()
    {
        return $this->belongsTo(\Modules\TsRaw\Models\BalanceHeader::class, 'id_balance_head', 'id_balance_head');
    }

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
        return $this->hasMany(\Modules\TsRaw\Models\TraceDetail::class, 'id_trace_head', 'id_trace_head')
                    ->where('status', 1);
    }

    public function materialDocument()
    {
        return $this->hasOne(\Modules\TsRaw\Models\MaterialDocument::class, 'id_trace_head', 'id_trace_head');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
