<?php
declare(strict_types=1);
namespace Modules\TraceForward\Models;

use Illuminate\Database\Eloquent\Model;

class TraceHeader extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 't_trace_header';
    protected $primaryKey = 'id_trace_head';
    public $timestamps = true;

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

    public function details()
    {
        return $this->hasMany(TraceDetail::class, 'id_trace_head', 'id_trace_head')
                    ->where('status', 1);
    }

    public function materialDocument()
    {
        return $this->hasOne(MaterialDocument::class, 'id_trace_head', 'id_trace_head');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
