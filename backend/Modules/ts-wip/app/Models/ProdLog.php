<?php

namespace Modules\TsWip\Models;

// GAP #10: Add dedicated Models for WIP Entry module
use Illuminate\Database\Eloquent\Model;

/**
 * ProdLog Model - Production log for WIP processing
 *
 * Tracks production batches for WIP processing.
 * Inserted after successful feed, cleaned up on feed cancellation.
 */
class ProdLog extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 't_prod_log';
    protected $primaryKey = 'id_prod_log';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id_trace_head',
        'section',
        'entry_date',
        'batch_no',
        'tank_id',
        'tank_tail',
        'id_material',
        'in_qty',
        'out_qty',
        'yield',
        'id_plant',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'in_qty' => 'decimal:4',
        'out_qty' => 'decimal:4',
        'yield' => 'decimal:4',
        'status' => 'integer',
        'tank_tail' => 'array',
    ];

    /**
     * Get the trace header associated with this production log
     */
    public function traceHeader()
    {
        return $this->belongsTo(\Modules\TsRaw\Models\TraceHeader::class, 'id_trace_head', 'id_trace_head');
    }

    /**
     * Get the material associated with this production log
     */
    public function material()
    {
        return $this->belongsTo(\Modules\Material\Models\Material::class, 'id_material', 'id_material');
    }

    /**
     * Get the tank associated with this production log
     */
    public function tank()
    {
        return $this->belongsTo(\Modules\Tank\Models\Tank::class, 'tank_id', 'id_sloc');
    }

    /**
     * Scope to get active production logs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to filter by section
     */
    public function scopeBySection($query, string $section)
    {
        return $query->where('section', $section);
    }

    /**
     * Scope to filter by plant
     */
    public function scopeByPlant($query, $plantId)
    {
        return $query->where('id_plant', $plantId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }
}