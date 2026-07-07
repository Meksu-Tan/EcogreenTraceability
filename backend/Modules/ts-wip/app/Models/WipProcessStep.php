<?php

declare(strict_types=1);

namespace Modules\TsWip\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WipProcessStep extends Model
{
    protected $connection = 'eudr_ts';

    protected $table = 'm_wip_process_step';

    protected $primaryKey = 'id';

    protected $fillable = [
        'section_id', 'parent_step_id', 'step_type', 'label',
        'feed_id', 'rundown_id', 'pipe_number', 'dcs_tag',
        'mode_group', 'mode_value', 'conditions', 'mode_options',
        'sort_order', 'status',
    ];

    protected $casts = [
        'section_id' => 'integer',
        'parent_step_id' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'conditions' => 'array',
        'mode_options' => 'array',
    ];

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public function section(): BelongsTo
    {
        return $this->belongsTo(WipSection::class, 'section_id', 'id');
    }
}
