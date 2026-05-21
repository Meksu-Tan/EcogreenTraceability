<?php

namespace Modules\Storage\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $table      = 'm_warehouse';
    protected $primaryKey = 'id_warehouse';

    protected $fillable = [
        'id_batch',
        'code',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = false;
}
