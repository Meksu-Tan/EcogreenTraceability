<?php

namespace App\Models;

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
        'plant_code',
        'plant_name',
        'tank_number',
        'description',
        'tank_height',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // Relationships
    public function details()
    {
        return $this->hasMany(TankDetail::class, 'id_sloc', 'id_sloc')
                    ->where('status', 1);
    }

    public function balanceHeaders()
    {
        return $this->hasMany(BalanceHeader::class, 'id_tank', 'id_sloc');
    }

    // Scopes
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
        return $query->where('plant_code', $plantId);
    }
}
