<?php
declare(strict_types=1);
namespace Modules\TsWip\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WipSection extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 'm_wip_section';
    protected $primaryKey = 'id';

    protected $fillable = [
        'code', 'name', 'plant_id', 'sort_order', 'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'status' => 'integer',
    ];

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function steps(): HasMany
    {
        return $this->hasMany(WipProcessStep::class, 'section_id', 'id');
    }
}
