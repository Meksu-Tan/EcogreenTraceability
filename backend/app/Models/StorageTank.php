<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageTank extends Model
{
    protected $table      = 'm_tank';
    protected $primaryKey = 'id_tank';

    protected $fillable = [
        'code_2',
        'code_3',
        'code_4',
        'id_plant',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = false;

    public function details()
    {
        return $this->hasMany(StorageDetail::class, 'id_tank', 'id_tank');
    }
}
