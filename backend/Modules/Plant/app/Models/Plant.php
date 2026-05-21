<?php

namespace Modules\Plant\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    protected $table      = 'm_plant';
    protected $primaryKey = 'id_plant';

    protected $fillable = [
        'code_2',
        'code_3',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = true;
}
