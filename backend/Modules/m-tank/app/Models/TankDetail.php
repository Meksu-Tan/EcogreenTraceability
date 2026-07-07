<?php

declare(strict_types=1);

namespace Modules\Tank\Models;

use Illuminate\Database\Eloquent\Model;

class TankDetail extends Model
{
    protected $connection = 'eudr_ts';

    protected $table = 'm_sloc_detail';

    protected $primaryKey = 'id_sloc_tail';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id_sloc',
        'tf_number',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'integer',
        'id_sloc' => 'integer',
    ];

    public function tank()
    {
        return $this->belongsTo(Tank::class, 'id_sloc', 'id_sloc');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
