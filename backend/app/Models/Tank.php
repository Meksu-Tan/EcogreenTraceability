<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tank extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 'm_tank';
    protected $primaryKey = 'id_tank';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'code',
        'code_2',
        'code_3',
        'code_4',
        'id_plant',
        'description',
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
        return $this->hasMany(TankDetail::class, 'id_tank', 'id_tank')
                    ->where('status', 1);
    }

    public function balanceHeaders()
    {
        return $this->hasMany(BalanceHeader::class, 'id_tank', 'id_tank');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeStorage($query)
    {
        return $query->where('code_3', 'STORAGE');
    }

    public function scopeFeed($query)
    {
        return $query->where('code_3', 'FEED');
    }

    public function scopeByPlant($query, $plantId)
    {
        return $query->where('id_plant', $plantId);
    }
}
