<?php
declare(strict_types=1);
namespace Modules\Tank\Models;

use Illuminate\Database\Eloquent\Model;

class Tank extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 'm_sloc';
    protected $primaryKey = 'id_sloc';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id_sloc',
        'id_plant',
        'plant_name',
        'tf_number',
        'description',
        'code_3',
        'tank_height',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // Accessor for compatibility - tank_number returns tf_number
    public function getTankNumberAttribute()
    {
        return $this->tf_number;
    }

    public function details()
    {
        return $this->hasMany(TankDetail::class, 'id_sloc', 'id_sloc')
                    ->where('status', 1);
    }

    public function balanceHeaders()
    {
        return $this->hasMany(\Modules\TsRaw\Models\BalanceHeader::class, 'id_sloc', 'id_sloc');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeStorage($query)
    {
        return $query->whereRaw("description ILIKE '%STORAGE%'");
    }

    public function scopeFeed($query)
    {
        return $query->whereRaw("description ILIKE '%FEED%'");
    }

    public function scopeByPlant($query, $plantId)
    {
        return $query->where('id_plant', $plantId);
    }
}
