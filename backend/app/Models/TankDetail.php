<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TankDetail extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 'm_tank_detail';
    protected $primaryKey = 'id_tank_tail';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id_tank',
        'tf_number',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'integer',
        'id_tank' => 'integer',
    ];

    // Relationships
    public function tank()
    {
        return $this->belongsTo(Tank::class, 'id_tank', 'id_tank');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
