<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageDetail extends Model
{
    protected $table      = 'm_tank_detail';
    protected $primaryKey = 'id_tank_tail';

    protected $fillable = [
        'id_tank',
        'tf_number',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = false;

    public function tank()
    {
        return $this->belongsTo(StorageTank::class, 'id_tank', 'id_tank');
    }
}
