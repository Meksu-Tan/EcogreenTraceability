<?php

namespace Modules\Manufacturer\Models;

use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    protected $table      = 'm_manufacturer';
    protected $primaryKey = 'id_manufacturer';

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
}
