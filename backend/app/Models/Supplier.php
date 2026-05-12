<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table      = 'm_supplier';
    protected $primaryKey = 'id_supplier';

    protected $fillable = [
        'code',
        'batch_code',
        'description',
        'type',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = false;

    public function storageTank()
    {
        return $this->belongsTo(StorageTank::class, 'type', 'id_tank');
    }
}
