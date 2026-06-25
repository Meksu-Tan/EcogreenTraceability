<?php
declare(strict_types=1);
namespace Modules\TraceForward\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceHeader extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 't_balance_header';
    protected $primaryKey = 'id_balance_head';
    public $timestamps = true;

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

    public function traceHeaders()
    {
        return $this->hasMany(TraceHeader::class, 'id_balance_head', 'id_balance_head');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeRmEntry($query)
    {
        return $query->where(function ($q): void {
            $q->where('trace_no', 'LIKE', '1%')->orWhere('trace_no', 'LIKE', '9%');
        });
    }
}
