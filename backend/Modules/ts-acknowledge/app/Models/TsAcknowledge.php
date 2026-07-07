<?php

declare(strict_types=1);

namespace Modules\TsAcknowledge\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Plant\Models\Plant;
use Modules\TsWip\Models\WipSection;

class TsAcknowledge extends Model
{
    use SoftDeletes;

    protected $connection = 'eudr_ts';

    protected $table = 't_ts_acknowledge';

    protected $fillable = [
        'plant_code',
        'entry_date',
        'type',
        'transaction_id',
        'trace_no',
        'material_name',
        'source_name',
        'section_id',
        'mode_value',
        'step_type',
        'eo_dls_qty',
        'dcs_qty',
        'manual_qty',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'section_id' => 'integer',
        'eo_dls_qty' => 'decimal:4',
        'dcs_qty' => 'decimal:4',
        'manual_qty' => 'decimal:4',
    ];

    public function plant()
    {
        return $this->belongsTo(Plant::class, 'plant_code', 'code_3');
    }

    public function section()
    {
        return $this->belongsTo(WipSection::class, 'section_id', 'id');
    }
}
