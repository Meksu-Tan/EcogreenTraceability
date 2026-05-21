<?php

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
        'id_plant',
        'plant_name',
        'id_tank',
        'description',
        'tank_height',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // Accessor for compatibility - tank_number returns id_tank
    public function getTankNumberAttribute()
    {
        return $this->id_tank;
    }

    public function details()
    {
        return $this->hasMany(TankDetail::class, 'id_sloc', 'id_sloc')
                    ->where('status', 1);
    }

    public function balanceHeaders()
    {
        return $this->hasMany(\Modules\Transaction\Models\BalanceHeader::class, 'id_sloc', 'id_sloc');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeStorage($query)
    {
        return $query->where('description', 'like', '%Storage%');
    }

    public function scopeFeed($query)
    {
        return $query->where('description', 'like', '%Feed%');
    }

    public function scopeByPlant($query, $plantId)
    {
        return $query->where('id_plant', $plantId);
    }
}
